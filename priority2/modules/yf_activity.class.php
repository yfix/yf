<?php

/**
* Users activity handler
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_activity {

	/** @var int Number of records on page for raw log */
	var $RAW_LOG_PER_PAGE		= 50;
	/** @var bool Log spam (flood) tries */
	var $LOG_SPAM_TRIES			= true;
	/** @var bool Allow to decrement user's activity for revert actions (delete post, remove some record etc) */
	var $ALLOW_REMOVE_POINTS	= true;

	/**
	* YF module constructor
	*
	* @access	private
	* @return	void
	*/
	function _init () {
		// Get activity types
		$this->_activity_types = main()->get_data("activity_types");
	}

	/**
	* Default method
	*
	* @access	public
	* @return	string
	*/
	function show () {
		return $this->view();
	}

	/**
	* View activity status for the current user
	*
	* @access	public
	* @return	string
	*/
	function view () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Try to count total user activity points
		$total_points = $this->_get_user_total_points($this->USER_ID);
		// Process template
		$replace = array(
			"raw_log_link"	=> "./?object=".$_GET["object"]."&action=raw_log",
			"reput_link"	=> "./?object=reputation&action=view",
			"total_points"	=> intval($total_points),
			"actions_list"	=> $this->_show_actions_list(),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Raw activity log
	*
	* @access	public
	* @return	string
	*/
	function raw_log () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		if (!empty($_GET["id"])) {
			$_GET["page"] = $_GET["id"];
		}
		// Connect pager
		$sql = "SELECT * FROM `".db('activity_logs')."` WHERE `user_id`=".intval($this->USER_ID);
		$order_sql = " ORDER BY `add_date` DESC ";
		$url = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $url, null, $this->RAW_LOG_PER_PAGE);
		// Process items
		$Q = db()->query($sql. $order_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$items[] = array(
				"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
				"points"	=> intval($A["add_points"]),
				"add_date"	=> _format_date($A["add_date"], "long"),
				"task_name"	=> _prepare_html($this->_activity_types[$A["task_id"]]["name"]),
			);
		}
		// Process template
		$replace = array(
			"items"	=> $items,
			"pages"	=> $pages,
			"total"	=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/raw_log_main", $replace);
	}

	/**
	* Display activity actions list
	*
	* @access	public
	* @return	string
	*/
	function _show_actions_list () {
		// Process items
		foreach ((array)$this->_activity_types as $A) {
			$items[] = array(
				"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
				"name"		=> _prepare_html($A["name"]),
				"desc"		=> _prepare_html($A["desc"]),
				"points"	=> intval($A["points"]),
				"min_value"	=> !empty($A["min_value"]) ? intval($A["min_value"]) : "",
			);
		}
		// Process template
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse($_GET["object"]."/actions_list", $replace);
	}

	/**
	* Add points automatically after specified action (task)
	*
	* @example
	*	$ACTIVITY_OBJ = main()->init_class("activity");
	*	if (is_object($ACTIVITY_OBJ)) {
	*		$ACTIVITY_OBJ->_add_activity_points($this->BLOG_OBJ->USER_ID, "blog_post", strlen($_POST["post_text"]));
	*	}
	*
	* @example
	*	common()->_add_activity_points($this->BLOG_OBJ->USER_ID, "blog_post", strlen($_POST["post_text"]));
	*/
	function _auto_add_points ($user_id = 0, $task_name = "", $action_value = "", $record_id = 0, $remove_points = false) {
		// Process params as first argument array
		if (is_array($user_id)) {
			$params			= $user_id;
			$user_id		= $params["user_id"];
			$task_name		= $params["task_name"];
			$action_value	= $params["action_value"];
			$remove_points	= $params["remove_points"];
		}
		// Check required data
		if (empty($user_id) || empty($task_name)) {
			return false;
		}
		$action_value = intval($action_value);
		// Try to get task id
		$task_info = $this->_task_id_from_name($task_name, 1);
		if (empty($task_info)) {
			return false;
		}
		// Try to start activity account (if not done yet)
		$this->_start_activity_account($user_id);
		// Do special checking
		if (!$remove_points && $this->_spam_checking_on_add($user_id, $task_info, $action_value)) {
			return false;
		}
		// Revert number of points user will get (in "remove_points" mode)
		if ($remove_points) {
			$task_info["points"] *= -1;
		}
		// Do add activity points
		db()->INSERT("activity_logs", array(
			"task_id"	=> intval($task_info["id"]),
			"user_id"	=> intval($user_id),
			"add_points"=> intval($task_info["points"]),
			"add_date"	=> time(),
		));
		// Do save activity to the appropriate table
		if (!empty($record_id)) {
			$this->_update_external_table($task_info, $record_id);
		}
		// Do update user's activity
		$this->_update_user_activity($task_info, $user_id);
		// Update reputation
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$REPUT_OBJ->_update_user_reput_info($user_id);
		}
		return true;
	}

	/**
	* Remove points automatically after specified action (task)
	*
	* @see	$this->_auto_add_points()
	*/
	function _auto_remove_points ($user_id = 0, $task_name = "", $action_value = "", $record_id = 0) {
		if (!$this->ALLOW_REMOVE_POINTS) {
			return false;
		}
		// Process params as first argument array
		if (is_array($user_id)) {
			$params			= $user_id;
			$user_id		= $params["user_id"];
			$task_name		= $params["task_name"];
			$action_value	= $params["action_value"];
			$record_id		= $params["record_id"];
		}
		return $this->_auto_add_points($user_id, $task_name, $action_value, $record_id, true);
	}

	/**
	* Additional checking for some special actions
	*
	* @access	public
	* @return	string
	*/
	function _spam_checking_on_add ($user_id = 0, $task_info = array(), $action_value = "") {
		$spam_result	= 0;
		// No need to check if no limits set
		if (empty($task_info["min_value"]) && empty($task_info["min_time"])) {
			return $result;
		}
		$task_name	= $task_info["name"];
		// Check period between the same actions
		if (!empty($task_info["min_time"])) {
			list($spam_result) = db()->query_fetch(
				"SELECT `id` AS `0` FROM `".db('activity_logs')."` 
				WHERE `task_id`=".intval($task_info["id"])." 
					AND `user_id`=".intval($user_id)." 
					AND `add_date` > ".(time() - $task_info["min_time"])." 
				LIMIT 1"
			);
			if ($this->LOG_SPAM_TRIES && $spam_result) {
				trigger_error("Possible activity ".$task_name." spam!", E_USER_NOTICE);
			}
		}
		// For all other actions check if min_value is reached
		if (!empty($task_info["min_value"])) {
			if ($action_value < $task_info["min_value"]) {
				$spam_result = 1;
			}
		}
		return (bool) $spam_result;
	}

	/**
	* Return task ID by its name
	*
	* @access	public
	* @return	string
	*/
	function _task_id_from_name ($name = "login", $as_info_array = 0) {
		$task_id = 0;
		if (empty($name)) {
			return 0;
		}
		$name = strtolower($name);
		foreach ((array) $this->_activity_types as $a_id => $a_info) {
			if (strtolower($a_info["name"]) == $name) {
				$task_id = $a_id;
				break;
			}
		}
		return $as_info_array ? $this->_activity_types[$task_id] : $task_id;
	}

	/**
	* Get activity points
	*
	* @access	public
	* @return	string
	*/
	function _get_user_total_points ($user_id = 0) {
		$total_points = 0;
		if (empty($user_id)) {
			return false;
		}
		$A = db()->query_fetch("SELECT `points` FROM `".db('activity_total')."` WHERE `user_id`=".intval($user_id));
		if (empty($A)) {
			$this->_start_activity_account($user_id);
			$total_points = 0;
		} else {
			$total_points = $A["points"];
		}
		return $total_points;
	}

	/**
	* Get activity info (points and exchanged points)
	*
	* @access	public
	* @return	string
	*/
	function _get_user_total_info ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		// Get from db
		$activity_info = db()->query_fetch("SELECT * FROM `".db('activity_total')."` WHERE `user_id`=".intval($user_id));
		if (empty($activity_info)) {
			$this->_start_activity_account($user_id);
			$activity_info = array(
				"user_id"				=> $user_id,
				"points"				=> 0,
				"exchanged_act_points"	=> 0,
			);
		}
		return $activity_info;
	}

	/**
	* Create start activity account
	*
	* @access	private
	* @return	void
	*/
	function _start_activity_account ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		$ACCOUNT_EXISTS = db()->query_num_rows("SELECT `user_id` FROM `".db('activity_total')."` WHERE `user_id`=".intval($user_id));
		if ($ACCOUNT_EXISTS) {
			return false;
		}
		// Process SQL
		db()->INSERT("activity_total", array(
			"user_id" 		=> intval($user_id),
			"points" 		=> 0,
			"last_update"	=> time(),
		));
	}

	/**
	* Do update activity value in external table
	*
	* @access	private
	* @return	void
	*/
	function _update_external_table ($task_info = array(), $record_id = 0) {
		if (empty($record_id) || empty($task_info) || empty($task_info["table_name"])) {
			return false;
		}
		$points = $task_info["points"];
		if ($points < 0) {
			$points = 0;
		}
		db()->UPDATE($task_info["table_name"], array(
			"activity"	=> intval($points),
		), "`id`=".intval($record_id));
	}

	/**
	* Synchronize user's activity points
	*
	* @access	private
	* @return	void
	*/
	function _update_user_activity ($task_info = array(), $user_id = 0) {
		if (empty($user_id) || empty($task_info)) {
			return false;
		}
		$user_id = intval($user_id);
		$points_sum = 0;
		// Gather activity sum
		$sql_array = $this->_sql_arrays_for_update();
		$_sql_start		= "SELECT SUM(`t2`.`activity`) AS `0` FROM ";
		foreach ((array)$sql_array as $_counter => $_value) {
			$sql_array[$_counter] = $_sql_start. str_replace("{_USER_ID_}", $user_id, $_value);
		}
		$_sql_keys = array_keys($sql_array);
		// Get and assign unified data
		foreach ((array)db()->query_fetch_all("(".implode(") UNION ALL (", $sql_array).")") as $_counter => $_value) {
			$totals[$_sql_keys[$_counter]] = $_value[0];
		}
		$points_sum = array_sum($totals);
		// Update total table for current user
		$sql = "UPDATE `".db('activity_total')."` SET 
				`points`		= ".intval($points_sum).", 
				`last_update`	= ".time()." 
			WHERE `user_id`=".intval($user_id);
		db()->_add_shutdown_query($sql);
	}

	/**
	* SQL arrays for re-count user's activity (to store them in one place)
	*
	* @access	private
	* @return	void
	*/
	function _sql_arrays_for_update ($for_cron_job = false) {
		// I put it here for purpose of "One point of failure" for changes
		if ($for_cron_job) {
			$sql_array = array(
				"forum_post"		=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('forum_posts')."`			WHERE 1							GROUP BY `user_id`",
				"sent_mail"			=> "SELECT `sender`, SUM(`activity`)		FROM `".db('mailarchive')."`			WHERE 1							GROUP BY `sender`",
				"rate_user"			=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('reput_user_votes')."`		WHERE 1							GROUP BY `user_id`",
				"blog_post"			=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('blog_posts')."` 			WHERE 1							GROUP BY `user_id`",
				"site_login"		=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('log_auth')."` 			WHERE 1							GROUP BY `user_id`",
				"blog_comment"		=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('comments')."` 			WHERE `object_name`='blog'		GROUP BY `user_id`",
				"bug_report"		=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('help_tickets')."`			WHERE 1							GROUP BY `user_id`",
				"article_posted"	=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('articles_texts')."`		WHERE 1							GROUP BY `user_id`",
				"article_reposted"	=> "SELECT `user_id`, SUM(`activity`)		FROM `".db('articles_texts')."`		WHERE 1							GROUP BY `user_id`",
			);
		} else {
			$sql_array = array(
				"forum_post"		=> "`".db('forum_posts')."` AS `t2`		WHERE `t2`.`user_id`={_USER_ID_}",
				"sent_mail"			=> "`".db('mailarchive')."` AS `t2`		WHERE `t2`.`sender`={_USER_ID_}",
				"rate_user"			=> "`".db('reput_user_votes')."` AS `t2`	WHERE `t2`.`user_id`={_USER_ID_}",
				"blog_post"			=> "`".db('blog_posts')."` AS `t2` 		WHERE `t2`.`user_id`={_USER_ID_}",
				"site_login"		=> "`".db('log_auth')."` AS `t2` 			WHERE `t2`.`user_id`={_USER_ID_}",
				"blog_comment"		=> "`".db('comments')."` AS `t2` 			WHERE `t2`.`object_name`='blog' AND `t2`.`user_id`={_USER_ID_}",
				"bug_report"		=> "`".db('help_tickets')."` AS `t2`		WHERE `t2`.`user_id`={_USER_ID_}",
				"article_posted"	=> "`".db('articles_texts')."` AS `t2`	WHERE `t2`.`user_id`={_USER_ID_}",
				"article_reposted"	=> "`".db('articles_texts')."` AS `t2`	WHERE `t2`.`user_id`={_USER_ID_}",
			);
		}
		return $sql_array;
	}

	/**
    * Update activity for all users (use only when number of user is small !)
	*/
	function _sync_all_users_activity () {
		$Q = db()->query("SELECT `id` FROM `".db('user')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_update_user_activity(1, $A["id"]);
		}
	}
}
