<?php

/**
* User reputation handler
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_reputation {

	/** @var int Initial user reputation points */
	var $START_REPUT			= 10;
	/** @var int Initial user reputation alt */
	var $START_REPUT_ALT		= 1;
	/** @var int Minimum reputation points to alter other member's reputation */
	var $MIN_REPUT				= 2;
	/** @var int Minimum activity points to alter other member's reputation */
	var $MIN_ACTIVITY			= 3;
	/** @var int +1 altering power for every this number of activity points */
	var $ACT_REPUT_VALUE		= 150;
	/** @var int +1 altering power for every this number of reputation points */
	var $REPUT_ADD_ALT_POINTS	= 20;
	/** @var int Maximum number of allowed reputation clicks at one day */
	var $MAX_REPUT_CLICKS_DAILY	= 10;
	/** @var int Number of other users to vote on until you can vote on the same user again */
	var $REPUT_CAST_VALUE		= 2;
	/** @var int Number of last votes to track reputation changing */
	var $REPUT_CHANGE_NUM_LAST	= 10;
	/** @var array Array of values to display on each star */
	var $STARS_VALUES_ARRAY		= array(
		-5	=> -100,
		-4	=> -50,
		-3	=> -20,
		-2	=> -10,
		-1	=> -1,
		1	=> 10,
		2	=> 20,
		3	=> 30,
		4	=> 50,
		5	=> 100,
	);
	/** @var int @conf_skip Current user reput points */
	var $CUR_USER_REPUT_POINTS	= null;
	/** @var int @conf_skip Current user reput altering power */
	var $CUR_USER_ALT_POWER		= null;
	/** @var int @conf_skip Current user number of reput votes made */
	var $CUR_USER_NUM_VOTES		= null;
	/** @var int @conf_skip Current user voted by others times */
	var $CUR_USER_NUM_VOTED		= null;
	/** @var int @conf_skip Current user reputation changing (-/+/0 points for the last $this->REPUT_CHANGE_NUM_LAST votes) */
	var $CUR_USER_REPUT_CHANGE	= null;
	/** @var array @conf_skip Current user reput info array (contains all reputation info) */
	var $CUR_USER_REPUT_ARRAY	= null;
	/** @var int Number of user votes when we will start to analyse him for cheating */
	var $ANTICHEAT_START		= 15;
	/** @var float negative/total votes, if greater -> ignore vote */
	var $NEGATIVE_THRESHOLD		= 0.8;
	/** @var float positive/total votes, if greater -> ignore vote */
	var $SINGLE_USER_THRESHOLD	= 0.5;
	/** @var float "accounts voted negatively" / " total voted accounts", if lower -> ignore vote */
	var $CONCENTRATION_THRESHOLD= 0.1;
	/** @var float "voter num votes for voted user" / "total votes for voted user", if greater -> ignore vote */
	var $SINGLE_VOTER_THRESHOLD	= 0.8;
	/** @var bool Add auto-generated anti-cheat comment to log */
	var $ADD_ANTICHEAT_COMMENT	= true;
	/** @var bool Check multi-account cookie match when voting */
	var $VOTE_CHECK_COOKIE_MATCH= true;
	/** @var bool Check multi-IPs match when voting */
	var $VOTE_CHECK_MULTI_IPS	= true;
	/** @var bool Track object name and id (voted for) if provided */
	var $TRACK_OBJECT_INFO		= true;
	/** @var int Multi-IPs match TTL when voting, days */
	var $VOTE_MULTI_IP_TTL		= 30;
	/** @var array Mapping of vote pages */
	var $_map_vote_for = array(
		"forum_posts"	=> "./?object=forum&action=view_post&id=",
		"forum"			=> "./?object=forum&action=view_post&id=",
		"articles_texts"=> "./?object=articles&action=view&id=",
		"articles"		=> "./?object=articles&action=view&id=",
		"blog_posts"	=> "./?object=blogs&action=show_single_post&id=",
		"blog"			=> "./?object=blog&action=show_single_post&id=",
		"comments"		=> "./?object=comments&action=edit&id=",
		"reviews"		=> "./?object=reviews&action=view_details&id=",
	);
	/** @var array pairs object=comment_action */
	var $_comments_actions	= array(
		"articles"		=> "view",
		"blog"			=> "show_single_post",
		"faq"			=> "view",
		"gallery"		=> "show_medium_size",
		"help"			=> "view_answers",
		"news"			=> "full_news",
		"que"			=> "view",
		"reviews"		=> "view_details",
		"user_profile"	=> "show",
	);

	/**
	* YF module constructor
	*
	* @access	private
	* @return	void
	*/
	function _init () {
		define("REPUT_CLASS_NAME", "reputation");
		// Sub modules folder
		define("REPUT_MODULES_DIR", USER_MODULES_DIR. REPUT_CLASS_NAME."/");
		// Do get reputation info about current user (if is a member)
		if (!empty($this->USER_ID)) {
			$this->_get_cur_user_reput_info();
		}
	}

	/**
	* Default method
	* 
	* @access	public
	* @param
	* @return	string
	*/
	function show () {
		// Get top users by reputation points
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` ORDER BY `points` DESC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$max_reput[$A["user_id"]]	= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get bottom users by reputation points
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` ORDER BY `points` ASC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$min_reput[$A["user_id"]]	= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get top users by altering power
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` ORDER BY `alt_power` DESC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$max_alt[$A["user_id"]]		= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get bottom users by altering power
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` ORDER BY `alt_power` ASC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$min_alt[$A["user_id"]]		= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get top users by num_voted
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` ORDER BY `num_voted` DESC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$top_voted_for[$A["user_id"]]	= $A;
			$users_ids[$A["user_id"]]		= $A["user_id"];
		}
		// Get top users by number of votes done
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` ORDER BY `num_votes` DESC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$top_by_votes[$A["user_id"]]	= $A;
			$users_ids[$A["user_id"]]		= $A["user_id"];
		}
		// Get latest reputation votes
		$Q = db()->query("SELECT * FROM `".db('reput_user_votes')."` ORDER BY `add_date` DESC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$latest_votes[$A["id"]]			= $A;
			$users_ids[$A["user_id"]]		= $A["user_id"];
			$users_ids[$A["target_user_id"]]= $A["target_user_id"];
		}
		// Get most active users
		$Q = db()->query("SELECT * FROM `".db('activity_total')."` ORDER BY `points` DESC LIMIT 10");
		while ($A = db()->fetch_assoc($Q)) {
			$top_active[$A["user_id"]]		= $A;
			$users_ids[$A["user_id"]]		= $A["user_id"];
		}
		// Get users infos
		unset($users_ids[""]);
		if (!empty($users_ids))	{
			foreach ((array)user($users_ids, array("id","name","nick","photo_verified"), array("WHERE" => array("active" => 1))) as $A) {
				$this->_users_names[$A["id"]] = _display_name($A);
				$GLOBALS['verified_photos'][$A["id"]] = $A["photo_verified"];
			} 
		}
		// Process template
		$replace = array(
			"max_reput"		=> $this->_show_stats_item($max_reput),
			"min_reput"		=> $this->_show_stats_item($min_reput),
			"max_alt_power"	=> $this->_show_stats_item($max_alt),
			"min_alt_power"	=> $this->_show_stats_item($min_alt),
			"top_voted_for"	=> $this->_show_stats_item($top_voted_for),
			"top_by_votes"	=> $this->_show_stats_item($top_by_votes),
			"latest_votes"	=> $this->_show_stats_latest_item($latest_votes),
			"own_reput_link"=> $this->USER_ID ? "./?object=".$_GET["object"]."&action=view" : "",
		);
		return tpl()->parse($_GET["object"]."/stats_main", $replace);
	}

	/**
	* Show stats item
	* 
	* @access	private
	* @param
	* @return	string
	*/
	function _show_stats_item ($info_array = array()) {
		foreach ((array)$info_array as $A) {
			// Skip non existed users
			if (!isset($this->_users_names[$A["user_id"]])) {
				continue;
			}
			// Process template
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"	=> $i,
				"avatar"		=> _show_avatar($A["user_id"], $this->_users_names[$A["user_id"]], 1),
				"user_name"		=> _prepare_html($this->_users_names[$A["user_id"]]),
				"user_link"		=> _profile_link($A["user_id"]),
				"num_points"	=> intval($A["points"]),
				"alt_power"		=> intval($A["alt_power"]),
				"num_votes"		=> intval($A["num_votes"]),
				"num_voted"		=> intval($A["num_voted"]),
			);
			$body .= tpl()->parse($_GET["object"]."/stats_item", $replace2);
		}
		return $body;
	}

	/**
	* Show stats item for the latest votes
	* 
	* @access	private
	* @param
	* @return	string
	*/
	function _show_stats_latest_item ($info_array = array()) {
// TODO
		return "";
	}

	/**
	* View reputation for the current user
	* 
	* @access	public
	* @param
	* @return	string
	*/
	function view () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Process template
		$replace = array(
			"activity_link"			=> "./?object=activity&action=view",
			// For current user
			"reput_stars"			=> $this->_show_reput_stars($this->CUR_USER_REPUT_POINTS),
			"total_points"			=> intval($this->CUR_USER_REPUT_POINTS),
			"alt_power"				=> intval($this->CUR_USER_ALT_POWER),
			"num_votes"				=> intval($this->CUR_USER_NUM_VOTES),
			"num_voted"				=> intval($this->CUR_USER_NUM_VOTED),
			// Common limits
			"start_reput_points"	=> intval($this->START_REPUT),
			"start_reput_alt"		=> intval($this->START_REPUT_ALT),
			"min_reput"				=> intval($this->MIN_REPUT),
			"min_activity"			=> intval($this->MIN_ACTIVITY),
			"act_reput_value"		=> intval($this->ACT_REPUT_VALUE),
			"reput_add_alt_points"	=> intval($this->REPUT_ADD_ALT_POINTS),
			"max_reput_clicks"		=> intval($this->MAX_REPUT_CLICKS_DAILY),
			"reput_cast_value"		=> intval($this->REPUT_CAST_VALUE),
			// Last voted for
			"recent_votes"			=> $this->_show_recent_votes(),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Show block of recent votes done for the current user
	*/
	function _show_recent_votes() {
		$LIMIT_LAST_VOTES = 10;
		$Q = db()->query(
			"SELECT * FROM `".db('reput_user_votes')."` 
			WHERE `target_user_id`=".intval($this->USER_ID)." 
				AND `object_name` != '' 
			ORDER BY `add_date` DESC 
			LIMIT ".intval($LIMIT_LAST_VOTES)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A["id"]] = $A;
			if ($A["object_name"] == "comments") {
				$_comments_ids[$A["object_id"]] = $A["object_id"];
			}
		}
		// Get comments details
		if (!empty($_comments_ids)) {
			$Q = db()->query("SELECT `id`,`object_id`,`object_name` FROM `".db('comments')."` WHERE `id` IN(".implode(",", $_comments_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$_comments_details[$A["id"]] = $A;
			}
		}
		// Process records
		foreach ((array)$data as $A) {
			$url = "";
			if ($A["object_name"] == "comments") {
				$comment_info = $_comments_details[$A["object_id"]];
				$url = "./?object=".$comment_info["object_name"]."&action=".$this->_comments_actions[$comment_info["object_name"]]."&id=".$comment_info["object_id"]."#cid_".$comment_info["id"];
			} elseif (!empty($A["object_name"])) {
				$url = $this->_map_vote_for[$A["object_name"]].$A["object_id"];
			}
			$votes[$A["id"]] = array(
				"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
				"url"		=> $url,
				"direction"	=> $A["counted"] >= 0 ? "+" : "-",
			);
		}
		if (empty($votes)) {
			return false;
		}
		// Prepae template
		$replace = array(
			"votes"		=> $votes,
			"limit"		=> $LIMIT_LAST_VOTES,
		);
		return tpl()->parse(REPUT_CLASS_NAME."/recent_votes", $replace);
	}

	/**
	* Show reputation block for given user (could be shown in any place)
	*/
	function _show_for_user ($user_id = 0, $reput_info = array(), $HIDE_POPUP_LINK = false, $object_info = array()) {
		if (empty($user_id)) {
			return false;
		}
		// Start user reput account if not yet
		if (!empty($user_id) && empty($reput_info)) {
			$this->_start_reput_account($user_id);
		}
		// Fast checking if popup link needed
		$SHOW_LINK = false;
		if (!empty($this->USER_ID) && $user_id != $this->USER_ID && !$HIDE_POPUP_LINK) {
			$SHOW_LINK = true;
		}
		// Prepare object info if provided
		if ($this->TRACK_OBJECT_INFO && !empty($object_info) && count($object_info) == 2) {
			$object_link = "&page=".$object_info[0]."--".$object_info[1];
		}
		// Process template
		$replace = array(
			"reput_stars"	=> $this->_show_reput_stars($reput_info["points"]),
			"popup_link"	=> $SHOW_LINK ? process_url("./?object=".REPUT_CLASS_NAME."&action=vote_popup&id=".intval($user_id).$object_link) : "",
		);
		return tpl()->parse(REPUT_CLASS_NAME."/user_reput", $replace);
	}

	/**
	* Do vote for some user
	*/
	function vote () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		$target_user_id = $_GET["id"];
		// Parse object info
		if (!empty($_GET["page"]) && $this->TRACK_OBJECT_INFO) {
			list($this->_object_name, $this->_object_id) = explode("--", preg_replace("/[^0-9a-z_\-]/i", "", $_GET["page"]));
			if (!empty($this->_object_name) && !empty($this->_object_id)) {
				$object_link = "&page=".$this->_object_name."--".$this->_object_id;
			}
		}
		// Do check user id
		if (empty($target_user_id)) {
			common()->_raise_error(t("Missing target user ID!"));
		}
		// Check if user voting himself
		if ($this->USER_ID == $target_user_id) {
			common()->_raise_error(t("You are trying to vote for yourself!"));
		}
		if ($this->_user_info["ban_reput"]) {
			common()->_raise_error(
				"Sorry, you are not allowed to vote! Enjoy our site in some other way!"
				."For more details <a href=\"".process_url("./?object=faq&action=view&id=16")."\">click here</a>"
			);
		}
		// Check user info
		$user_info = user($target_user_id, "full", array("WHERE" => array("active" => 1)));

		if (empty($user_info)) {
			common()->_raise_error(t("No such user!"));
		}
		// Check voting changing direction (good / bad)
		$_POST["vote_change"] = intval($_POST["vote_change"]);
		$_vote_change = _my_range(-$this->CUR_USER_ALT_POWER, $this->CUR_USER_ALT_POWER);
		unset($_vote_change[0]);
		if (!in_array($_POST["vote_change"], $_vote_change)) {
			common()->_raise_error(t("Please specify vote changing direction (good / bad)!"));
		}
		// Check multi-accounts
		if (!common()->_error_exists()) {
			$this->_check_multi_accounts($target_user_id);
		}
		// Vote comment
		$_POST["comment"]	= "";
		$VOTE_VALUE			= 0;
		// Calculate vote value
		if (!common()->_error_exists()) {
			$VOTE_VALUE = $this->_calc_vote_value($target_user_id, $_POST["vote_change"]);
		}
		// Get last voter
		list($last_voter_id) = db()->query_fetch("SELECT `user_id` AS `0` FROM `".db('reput_user_votes')."` WHERE `target_user_id`=".intval($target_user_id)." ORDER BY `add_date` DESC LIMIT 1");
		// Check country (state) matching
		$country_match = 0;
		if (!empty($user_info["country"])) {
			if ($user_info["country"] == "US" && !empty($user_info["state"]) && $user_info["state"] == $this->_user_info["state"]) {
				$country_match = 1;
			} elseif ($user_info["country"] == $this->_user_info["country"]) {
				$country_match = 1;
			}
		}
		/************* ANTI-CHEAT *************/
		$stats["total_votes"] = $this->CUR_USER_REPUT_ARRAY["num_votes"];
		// First check if we need to enable anti-cheat checks
		if (!common()->_error_exists() && $stats["total_votes"] >= $this->ANTICHEAT_START) {
			// Collect stats for anti-cheat and make decision if cheat detected
			$this->CHEAT_DETECTED = false;
			// Get unified items stats
			$sql_array = array(
				"negative_total_votes"		=> "SELECT COUNT(*) AS `0` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." AND `counted` < 0",
				"positive_total_votes"		=> "SELECT COUNT(*) AS `0` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." AND `counted` > 0",
				"positive_single_user_votes"=> "SELECT MAX(`2`) AS `0` FROM (SELECT COUNT(*) AS `2` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." AND `counted` > 0 GROUP BY `target_user_id`) AS `1`",
				"positive_voted_accounts"	=> "SELECT COUNT(*) AS `0` FROM (SELECT COUNT(*) AS `2` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." AND `counted` > 0 GROUP BY `target_user_id`) AS `1`",
				"total_voted_accounts"		=> "SELECT COUNT(*) AS `0` FROM (SELECT COUNT(*) AS `2` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." GROUP BY `target_user_id`) AS `1`",
				"single_voter_target_votes"	=> "SELECT COUNT(*) AS `0` FROM `".db('reput_user_votes')."` WHERE `target_user_id`=".intval($target_user_id)." AND `user_id`=".intval($this->USER_ID)." AND `same_voter`='1'",
				"all_voters_target_votes"	=> "SELECT COUNT(*) AS `0` FROM `".db('reput_user_votes')."` WHERE `target_user_id`=".intval($target_user_id),
			);
			$_sql_keys = array_keys($sql_array);
			// Get and assign unified data
			foreach ((array)db()->query_fetch_all("(".implode(") UNION ALL (", $sql_array).")") as $_counter => $_value) {
				$stats[$_sql_keys[$_counter]] = $_value[0];
			}
			// Start checking
			$check_values[1] = $stats["negative_total_votes"] / $stats["total_votes"];
			if ($check_values[1] >= $this->NEGATIVE_THRESHOLD) {
				$this->CHEAT_DETECTED = true;
				$check_results[1] = 1;
			}
			$check_values[2] = $stats["positive_total_votes"] > 0 ? $stats["positive_single_user_votes"] / $stats["positive_total_votes"] : 0;
			if ($check_values[2] >= $this->SINGLE_USER_THRESHOLD) {
				$this->CHEAT_DETECTED = true;
				$check_results[2] = 1;
			}
			$check_values[3] = $stats["total_voted_accounts"] > 0 ? $stats["positive_voted_accounts"] / $stats["total_voted_accounts"] : 0;
			if ($check_values[3] < $this->CONCENTRATION_THRESHOLD) {
				$this->CHEAT_DETECTED = true;
				$check_results[3] = 1;
			}
			$check_values[4] = $stats["all_voters_target_votes"] > 0 ? $stats["single_voter_target_votes"] / $stats["all_voters_target_votes"] : 0;
			if ($check_values[4] >= $this->SINGLE_VOTER_THRESHOLD) {
				$this->CHEAT_DETECTED = true;
				$check_results[4] = 1;
			}
			if ($this->CHEAT_DETECTED && $this->ADD_ANTICHEAT_COMMENT) {
				$cheat_comment = 
				/*
					"anti-cheat stats:\r\n"
					.preg_replace("/^array \((.*?)[\,]{0,1}\)$/i", "\$1", str_replace(array("\r","\n"), " ", var_export($stats, 1)))
					."\r\ncheck_values:\r\n"
				*/
					""
					.round($check_values[1], 3)." >= ".$this->NEGATIVE_THRESHOLD."(".intval($check_results[1]).")".";"
					.round($check_values[2], 3)." >= ".$this->SINGLE_USER_THRESHOLD."(".intval($check_results[2]).")".";"
					.round($check_values[3], 3)." < ".$this->CONCENTRATION_THRESHOLD."(".intval($check_results[3]).")".";"
					.round($check_values[4], 3)." >= ".$this->SINGLE_VOTER_THRESHOLD."(".intval($check_results[4]).")".";"
					;
			}
		}
		// Get the URL to return user to
		$URL_RETURN_TO = isset($_POST["url_return_to"]) ? $_POST["url_return_to"] : $_SERVER["HTTP_REFERER"];
		// Check for errors and non-empty vote value
		if (!empty($VOTE_VALUE) && !common()->_error_exists()) {
			if ($this->CHEAT_DETECTED) {
				$penalty = intval(abs($VOTE_VALUE) * -1);
				// Do remove points from cheater
				db()->INSERT("reput_user_votes", array(
					"user_id"		=> intval($target_user_id),
					"target_user_id"=> intval($this->USER_ID),
					"voted"			=> intval($VOTE_VALUE),
					"comment"		=> _es($cheat_comment),
					"same_voter"	=> 0,
					"country_match"	=> 0,
					"counted"		=> $penalty,
					"penalty"		=> $penalty,
					"ip"			=> _es(common()->get_ip()),
					"add_date"		=> time(),
					"object_name"	=> _es($this->_object_name),
					"object_id"		=> intval($this->_object_id),
				));
			} else {
				// Store log
				db()->INSERT("reput_user_votes", array(
					"user_id"		=> intval($this->USER_ID),
					"target_user_id"=> intval($target_user_id),
					"voted"			=> intval($VOTE_VALUE),
					"comment"		=> _es($_POST["comment"]),
					"same_voter"	=> $last_voter_id == $this->USER_ID ? 1 : 0,
					"country_match"	=> $country_match,
					"counted"		=> intval($VOTE_VALUE),
					"penalty"		=> 0,
					"ip"			=> _es(common()->get_ip()),
					"add_date"		=> time(),
					"object_name"	=> _es($this->_object_name),
					"object_id"		=> intval($this->_object_id),
				));
			}
			$RECORD_ID = db()->INSERT_ID();
			// Do update users reput info from raw table
			$this->_update_user_reput_info($this->USER_ID);
			$this->_update_user_reput_info($target_user_id);
			// Save activity log
			if (!$this->CHEAT_DETECTED) {
				common()->_add_activity_points($this->USER_ID, "rate_user", "", $RECORD_ID);
			}
			// Process template
			$replace = array(
				"success"	=> 1,
				"text"		=> "",
			);
			$body = tpl()->parse(REPUT_CLASS_NAME."/vote_result", $replace);
		} else {
			// Error message
			$replace = array(
				"success"	=> 0,
				"text"		=> _e(),
			);
			$body = tpl()->parse(REPUT_CLASS_NAME."/vote_result", $replace);
		}
		return common()->show_empty_page($body);
	}

	/**
	* Show vote popup window
	*/
	function vote_popup () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$target_user_id = intval($_GET["id"]);
		// Parse object info
		if (!empty($_GET["page"]) && $this->TRACK_OBJECT_INFO) {
			list($this->_object_name, $this->_object_id) = explode("--", preg_replace("/[^0-9a-z_\-]/i", "", $_GET["page"]));
			if (!empty($this->_object_name) && !empty($this->_object_id)) {
				$object_link = "&page=".$this->_object_name."--".$this->_object_id;
			}
		}
		// Do check user id
		if (empty($target_user_id)) {
			common()->_raise_error(t("Missing target user ID!"));
		}
		// Check if user voting himself
		if ($this->USER_ID == $target_user_id) {
			common()->_raise_error(t("You are trying to vote for yourself!"));
		}
		if ($this->_user_info["ban_reput"]) {
			common()->_raise_error(
				"Sorry, you are not allowed to vote! Enjoy our site in some other way!"
				."For more details <a href=\"".process_url("./?object=faq&action=view&id=16")."\">click here</a>"
			);
		}
		// Check user info
		$user_info = user($target_user_id, "full", array("WHERE" => array("active" => 1)));
		if (empty($user_info)) {
			common()->_raise_error(t("No such user!"));
		}
		// Check multi-accounts
		if (!common()->_error_exists()) {
			$this->_check_multi_accounts($target_user_id);
		}
		if (!common()->_error_exists()) {
			$REPUT_INFO = $this->_get_user_reput_info($user_info["id"]);
		}
		// Check if vote allowed
		if (!common()->_error_exists()) {
			$VOTE_IS_ALLOWED = $this->_check_if_vote_allowed ($user_info["id"], $REPUT_INFO);
		}
		// Check for errors and process template
		if ($VOTE_IS_ALLOWED && !common()->_error_exists()) {
			$_vote_change = array();
			for ($i = -$this->CUR_USER_ALT_POWER; $i <= $this->CUR_USER_ALT_POWER; $i++) {
				if (!$i) {
					continue;
				}
				$_vote_change[$i] = ($i > 0 ? "+" : ""). $i;
			}
			$replace = array(
				"form_action"		=> process_url("./?object=".REPUT_CLASS_NAME."&action=vote&id=".$user_info["id"].$object_link),
				"reput_points"		=> intval($REPUT_INFO["points"]),
				"reput_stars"		=> $this->_show_reput_stars($REPUT_INFO["points"]),
				"url_return_to"		=> $_SERVER["HTTP_REFERER"],
				"user_name"			=> _display_name($user_info),
				"user_profile_link"	=> process_url("./?object=user_profile&action=show&id=".$target_user_id),
				"vote_change_box"	=> common()->select_box("vote_change", $_vote_change,	1, false, 2, "", false),
			);
			$body = tpl()->parse(REPUT_CLASS_NAME."/vote_popup", $replace);
		} else {
			$body = _e();
		}
		// Throw empty page
		return common()->show_empty_page($body);
	}

	/**
	* Multi-accounts checks
	*/
	function _check_multi_accounts ($target_user_id = 0) {
		if (empty($target_user_id) || empty($this->USER_ID)) {
			return false;
		}
		// Merge config
		$GLOBALS["PROJECT_CONF"] = my_array_merge((array)$GLOBALS["PROJECT_CONF"], array("multi_accounts" => array(
			"CHECK_COOKIE_MATCH"=> $this->VOTE_CHECK_COOKIE_MATCH,
			"CHECK_MULTI_IPS"	=> $this->VOTE_CHECK_MULTI_IPS,
			"MULTI_IP_TTL"		=> $this->VOTE_MULTI_IP_TTL,
		)));
		$MULTI_ACCOUNT_FOUND = common()->_check_multi_accounts($target_user_id, $this->USER_ID);
		// Raise error message if we found multi-account
		if ($MULTI_ACCOUNT_FOUND) {
			common()->_raise_error(t("Sorry, your vote seems suspicious to our anti-cheat filter and can't be counted!"));
		}
	}

	/**
	* Calculate vote value
	*/
	function _calc_vote_value ($target_user_id = 0, $VOTE_VALUE = 0) {
		if (common()->_error_exists()) {
			return false;
		}
		// Get current user reputation array
		$REPUT_INFO = $this->_get_user_reput_info($target_user_id);
		// Check if it is allowed
		if (!$this->_check_if_vote_allowed($target_user_id, $REPUT_INFO)) {
			return false;
		}
		// Check boundaries
		if (!empty($this->CUR_USER_ALT_POWER) && abs($VOTE_VALUE) > $this->CUR_USER_ALT_POWER) {
			$VOTE_VALUE = $this->CUR_USER_ALT_POWER;
		}
		return $VOTE_VALUE;
	}

	/**
	* Check if current user allowed to vote for another
	*/
	function _check_if_vote_allowed ($target_user_id = 0) {
		// Fast checks
		if (empty($target_user_id) || empty($this->USER_ID)) {
			common()->_raise_error(t("Missing required params for vote checking!"));
			return false;
		}
		if (!empty($this->USER_ID) && $target_user_id == $this->USER_ID) {
			common()->_raise_error(t("You are trying to vote for yourself!"));
			return false;
		}
		// Start more complex checks:
		// alt power
		if ($this->CUR_USER_ALT_POWER < 1) {
			common()->_raise_error(t("Sorry, but you have no reputation altering power... Please be more active on our site and you will be able to vote soon!"));
			return false;
		}
		// min reputation
		if (!empty($this->MIN_REPUT) && 
			$this->CUR_USER_REPUT_POINTS < $this->MIN_REPUT) {
			common()->_raise_error("You have too low site reputation (".intval($this->CUR_USER_REPUT_POINTS).")!<br />\r\nYou need at least ".intval($this->MIN_REPUT)."reputation points to be able to vote.");
			return false;
		}
		// min activity
		$this->CUR_USER_ACTIVITY = $this->_get_user_activity_points($this->USER_ID);
		if (!empty($this->MIN_ACTIVITY) && 
			$this->CUR_USER_ACTIVITY < $this->MIN_ACTIVITY) {
			common()->_raise_error("You have too low site activity (".intval($this->CUR_USER_ACTIVITY).")!<br />\r\nYou need minimum ".intval($this->MIN_ACTIVITY)." activity points to be able to vote. Please be more active on our site and you will be able to vote soon!");
			return false;
		}
		// clicks daily (for last 24 hours)
		list($this->REPUT_VOTES_LAST_24H) = db()->query_fetch("SELECT COUNT(`id`) AS `0` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." AND `add_date` > ".(time() - 86400));
		if (!empty($this->MAX_REPUT_CLICKS_DAILY) && 
			$this->REPUT_VOTES_LAST_24H >= $this->MAX_REPUT_CLICKS_DAILY) {
			common()->_raise_error("You have already spent all allowed daily votes! (".intval($this->MAX_REPUT_CLICKS_DAILY).") Thanks for being so active! To prevent our voting system from abuse we restrict the daily number of votes.");
			return false;
		}
		// cast value
		if (!empty($this->REPUT_CAST_VALUE)) {
			// Get last target users from this user votes limited with $this->REPUT_CAST_VALUE
			$Q = db()->query("SELECT `id`,`target_user_id` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($this->USER_ID)." ORDER BY `add_date` DESC LIMIT ".intval($this->REPUT_CAST_VALUE));
			while ($A = db()->fetch_assoc($Q)) $last_target_users[$A["id"]] = $A["target_user_id"];
			if (in_array($target_user_id, (array)$last_target_users)) {
				common()->_raise_error("You recently voted for this user. <br />\r\nSo to vote for him/her again you need to vote for at least ".intval($this->REPUT_CAST_VALUE)." other users first.");
				return false;
			}
		}
		// Check object id if already voted (allow to vote for the object_name->object_id only once)
		if ($this->TRACK_OBJECT_INFO && !empty($this->_object_name) && !empty($this->_object_id)) {
			$voted_for_object = db()->query_num_rows(
				"SELECT * 
				FROM `".db('reput_user_votes')."` 
				WHERE `user_id`=".intval($this->USER_ID)." 
					AND `object_id` = ".intval($this->_object_id)."
					AND `object_name` = '"._es($this->_object_name)."' 
				LIMIT 1"
			);
			if (!empty($voted_for_object)) {
				common()->_raise_error(t("You have already voted for this post!"));
				return false;
			}
		}
		return true;
	}

	/**
	* Update given user reput account
	*/
	function _update_user_reput_info ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		$ACCOUNT_EXISTS = db()->query_num_rows("SELECT `user_id` FROM `".db('reput_total')."` WHERE `user_id`=".intval($user_id));
		if (!$ACCOUNT_EXISTS) {
			return $this->_start_reput_account($user_id);
		}
		// Calculate user reput info from raw table
		list($total_points)	= db()->query_fetch("SELECT SUM(`counted`) AS `0` FROM `".db('reput_user_votes')."` WHERE `target_user_id`=".intval($user_id));
		$total_points += $this->START_REPUT;
		list($num_votes)	= db()->query_fetch("SELECT COUNT(`id`) AS `0` FROM `".db('reput_user_votes')."` WHERE `user_id`=".intval($user_id));
		list($num_voted)	= db()->query_fetch("SELECT COUNT(`id`) AS `0` FROM `".db('reput_user_votes')."` WHERE `target_user_id`=".intval($user_id));
		// Get user's activity
		$ACTIVITY_OBJ = main()->init_class("activity");
		if (is_object($ACTIVITY_OBJ)) {
			$activity_info	= $ACTIVITY_OBJ->_get_user_total_info($user_id);
			$act_points		= $activity_info["points"];
		}
		// Calculate alt power
		if ($total_points >= $this->MIN_REPUT) {
			$ALT_POWER = $this->START_REPUT_ALT;
			if (!empty($this->ACT_REPUT_VALUE) && $act_points >= $this->ACT_REPUT_VALUE) {
				$ALT_POWER += floor(log($act_points / $this->ACT_REPUT_VALUE));
			}
			if (!empty($this->REPUT_ADD_ALT_POINTS) && $total_points >= $this->REPUT_ADD_ALT_POINTS) {
				$ALT_POWER += floor($total_points / $this->REPUT_ADD_ALT_POINTS);
			}
		} else {
			$ALT_POWER = 0;
		}
		// Do save current user reputation points
		$sql = "UPDATE `".db('reput_total')."` SET 
				`points`	= ".intval($total_points).", 
				`alt_power`	= ".intval($ALT_POWER).", 
				`num_votes`	= ".intval($num_votes).", 
				`num_voted`	= ".intval($num_voted)."
			WHERE `user_id`=".intval($user_id);
		db()->query($sql);
	}

	/**
	* Get number of user's activity points
	*/
	function _get_user_activity_points ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		$activity_points = 0;
		// Connecting to the activity module
		$ACTIVITY_OBJ = main()->init_class("activity");
		if (is_object($ACTIVITY_OBJ)) {
			$activity_points = $ACTIVITY_OBJ->_get_user_total_points($user_id);
		}
		return intval($activity_points);
	}

	/**
	* Get reputation info array for given users ids (could be called from other modules)
	*/
	function _get_reput_info_for_user_ids ($users_ids = array()) {
		if (isset($users_ids[""])) {
			unset($users_ids[""]);
		}
		if (!is_array($users_ids) || empty($users_ids)) {
			return false;
		}
		$Q = db()->query("SELECT * FROM `".db('reput_total')."` WHERE `user_id` IN(".implode(",", $users_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$reput_infos[$A["user_id"]] = $A;
		}
		// Auto-start reput accounts
		foreach ((array)$users_ids as $_user_id) {
			if (isset($reput_infos[$_user_id])) {
				continue;
			}
			$reput_infos[$_user_id] = $this->_start_reput_account($_user_id);
		}
		return $reput_infos;
	}

	/**
	* Get reput info for the given user (with update)
	*/
	function _get_user_reput_info ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		if (!empty($this->USER_ID) && $user_id == $this->USER_ID && !empty($this->CUR_USER_REPUT_ARRAY)) {
			return $this->CUR_USER_REPUT_ARRAY;
		}
		// Try to get user reput account info
		$REPUT_INFO = db()->query_fetch("SELECT * FROM `".db('reput_total')."` WHERE `user_id`=".intval($user_id));
		if (empty($REPUT_INFO)) {
			// Do create user reput info (if not done yet)
			$this->_start_reput_account($user_id);
			// Try again
			$REPUT_INFO = db()->query_fetch("SELECT * FROM `".db('reput_total')."` WHERE `user_id`=".intval($user_id));
		}
		return $REPUT_INFO;
	}

	/**
	* Create start reputation account
	*/
	function _start_reput_account ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		$ACCOUNT_EXISTS = db()->query_num_rows("SELECT `user_id` FROM `".db('reput_total')."` WHERE `user_id`=".intval($user_id));
		if ($ACCOUNT_EXISTS) {
			return false;
		}
		// Process SQL
		db()->INSERT("reput_total", array(
			"points" 	=> intval($this->START_REPUT),
			"alt_power" => intval($this->START_REPUT_ALT),
			"num_votes" => 0,
			"num_voted" => 0,
			"user_id" 	=> intval($user_id),
		));
	}

	/**
	* Get reput info about current user
	*/
	function _get_cur_user_reput_info () {
		$REPUT_INFO = $this->_get_user_reput_info($this->USER_ID);
		// Fill current user info
		if (!empty($REPUT_INFO)) {
			$this->CUR_USER_REPUT_POINTS	= intval($REPUT_INFO["points"]);
			$this->CUR_USER_ALT_POWER		= intval($REPUT_INFO["alt_power"]);
			$this->CUR_USER_NUM_VOTES		= intval($REPUT_INFO["num_votes"]);
			$this->CUR_USER_NUM_VOTED		= intval($REPUT_INFO["num_voted"]);

			$this->CUR_USER_REPUT_ARRAY		= $REPUT_INFO;
		}
	}

	/**
	* Show stars for the given reputation value
	*/
	function _show_reput_stars ($reput_value = 0, $_no_reput_number = false) {
		$reput_value = intval($reput_value);
		// Total stars to fill with values
		$stars_to_fill = ceil(count($this->STARS_VALUES_ARRAY) / 2);
		// Do get stars templates
		$this->_star_good		= tpl()->parse(REPUT_CLASS_NAME."/stars", array(
			"good"			=> 1,
			"reput_value"	=> $reput_value,
			"no_number"		=> (int)$_no_reput_number,
		));
		$this->_star_grey		= tpl()->parse(REPUT_CLASS_NAME."/stars", array(
			"grey"			=> 1,
			"reput_value"	=> $reput_value,
			"no_number"		=> (int)$_no_reput_number,
		));
		$this->_star_bad		= tpl()->parse(REPUT_CLASS_NAME."/stars", array(
			"bad"			=> 1,
			"reput_value"	=> $reput_value,
			"no_number"		=> (int)$_no_reput_number,
		));
		$this->_star_neutral	= tpl()->parse(REPUT_CLASS_NAME."/stars", array(
			"neutral"		=> 1,
			"reput_value"	=> $reput_value,
			"no_number"		=> (int)$_no_reput_number,
		));
		// Do fill stars with proper values
		if ($reput_value == 0) {
			for ($i = 1; $i <= $stars_to_fill; $i++) {
				$body .= $this->_star_grey;
			}
		} elseif ($reput_value > 0) {
			for ($i = 1; $i <= $stars_to_fill; $i++) {
				$body .= ($reput_value >= $this->STARS_VALUES_ARRAY[$i]) ? $this->_star_good : $this->_star_grey;
			}
		} elseif ($reput_value < 0) {
			for ($i = -1; $i >= -$stars_to_fill; $i--) {
				$body .= ($reput_value <= $this->STARS_VALUES_ARRAY[$i]) ? $this->_star_bad : $this->_star_grey;
			}
		}
		return $body;
	}

	/**
	* Try to load sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ = main()->init_class($module_name, REPUT_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("REPUTATION: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}

	/**
	* task for cron or task manager
	*
	* @access	public
	* @return	
	*/
	function _do_cron_job() {
		$OBJ = $this->_load_sub_module("reputation_sync");
		return is_object($OBJ) ? $OBJ->_do_cron_job() : "";
	}
}
