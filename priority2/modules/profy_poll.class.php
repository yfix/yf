<?php

/**
* Polls handler
*/
class profy_poll {

	/** @var int Max choices for answer */
	var $MAX_CHOICES			= 20;
	/** @var bool */
	var $PROCESS_STATUS_FIELD	= false;
	/** @var int Vote TTL */
	var $VOTE_TTL				= 86400;
	/** @var bool Restrict voting only one time for the poll */
	var $ONE_VOTE_FOR_USER		= false;
	/** @var bool */
	var $ALLOW_VIEW_FOR_GUESTS	= true;
	/** @var bool */
	var $ALLOW_VOTE_FOR_OWNER	= true;
	/** @var int */
	var $VIEW_MAX_WIDTH			= 250;
	/** @var array Add colors for poll bars here */
	var $VIEW_COLORS			= array(
		"", // !!! required. do not remove!
		"#8F9EFF",	//blue2
		"#7ACCB8",	//green2
		"#D03A12", 	//red
		"#8FB4FF", 	//blue1
		"#97CC7A",	//green3
		"#FFB13D",	//yellow
		"#82FF8A",	//green1
		"#A08FFF",	//blue3
		"#999999",	//grey
	);
	/** @var bool Allow to mark more than one choice */
	var $ALLOW_MULTI_CHOISES	= true;
	/** @var bool */
	var $DYNAMIC_QUE_ADDING		= true;
	
	/**
	* Default method and main controller
	*/
	function show ($params = array()) {
		// Get params
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] 		: $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"])		? intval($params["object_id"]) 	: intval($_GET["id"]);
		$STPL_NAME_MAIN = !empty($params["stpl_main"])		? $params["stpl_main"] 			: "poll/main";
		$POLL_ID 		= !empty($params["poll_id"])		? $params["poll_id"] 			: intval($_POST["poll_id"]);

		if (!empty($params["poll_id"])) {
			$POLL_ID = intval($params["poll_id"]);
		}

		// Auto-find poll id
		if ($params["object_name"] && $params["object_id"] && !$POLL_ID) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `object_name`='"._es($params["object_name"])."' AND `object_id`=".intval($params["object_id"]). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
			$POLL_ID = $poll_info["id"];
		}
		if (empty($POLL_ID)) {
			return "";
		}
		// Currently only for members (for guests display)
		if (empty($this->USER_ID)) {
			if ($this->ALLOW_VIEW_FOR_GUESTS) {
				return $this->view($params);
			} else {
				return !$params["silent"] ? _error_need_login() : false;
			}
		}
		if (!empty($POLL_ID) && !$params["silent"]) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `id`=".intval($POLL_ID). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
		}
		// Check required params
		if (empty($poll_info) && (empty($OBJECT_NAME) || empty($OBJECT_ID))) {
			return !$params["silent"] ? _e(t("Missing required params for poll!")) : false;
		}
		// Get poll info
		if (empty($poll_info)) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `object_name`='"._es($OBJECT_NAME)."' AND `object_id`=".intval($OBJECT_ID). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
		}
		if (empty($poll_info) && !empty($POLL_ID)) {
			return !$params["silent"] ? _e(t("No such poll!")) : false;
		}
		// Restore some missing params
		if (empty($OBJECT_NAME)) {
			$OBJECT_NAME	= $poll_info["object_name"];
		}
		if (empty($OBJECT_ID)) {
			$OBJECT_ID		= $poll_info["object_id"];
		}
		$FORM_ACTION	= !empty($params["form_action"])	? $params["form_action"] : "./?object=".$_GET["object"]."&action=".$_GET["action"].($_GET["id"] ? "&id=".$_GET["id"] : "");
		$RETURN_PATH	= !empty($params["return_path"])	? process_url($params["return_path"]) : (!empty($params["return_action"]) ? process_url("./?object=".$_GET["object"]."&action=".$params["return_action"]."&id=".$OBJECT_ID) : $_SERVER["HTTP_REFERER"]);
		$RESULTS_LINK	= !empty($params["results_link"])	? $params["results_link"] : "./?object=poll&action=owner_view&id=".intval($poll_info["id"]);
		// Prepare choices
		foreach ((array)explode("\n", str_replace("\r", "", $poll_info["choices"])) as $_id => $_text) {
			$choices[++$i] = array(
				"id"	=> intval($i),
				"text"	=> _prepare_html(_ucfirst($_text)),
			);
		}
		// Check if we do not need to display vote form because TTL is not expired yet
		$last_vote = db()->query_fetch("SELECT * FROM `".db('poll_votes')."` WHERE `user_id` = ".intval($this->USER_ID)." AND `poll_id` = ".intval($poll_info["id"])." ORDER BY `date` DESC");
		if (!empty($last_vote)) {
			if ($this->ONE_VOTE_FOR_USER || ($this->VOTE_TTL && (time() - $last_vote["date"]) < $this->VOTE_TTL)) {
				$_GET["id"] = $poll_info["id"];
				return $this->view($params, $poll_info);
			}
		}

		// Check posted data and save
		if (!empty($_POST) && $_POST["choice"]) {
			if (!empty($last_vote)) {
				if ($this->ONE_VOTE_FOR_USER) {
					common()->_raise_error(t("You allowed to vote only one time in this poll"));
				} elseif ($this->VOTE_TTL && (time() - $last_vote["date"]) < $this->VOTE_TTL) {
					common()->_raise_error("Please wait ".ceil((time() - $last_vote["date"]) / 60)." minutes before you can vote for this poll again");
				}
			}
			// Check if something selected
			if (empty($_POST["choice"])) {
				common()->_raise_error(t("Please select something"));
			}
			$_is_poll_owner = ($this->USER_ID && $this->USER_ID == $poll_info["user_id"]) ? 1 : 0;
			// Check if owner can vote
			$_allow_vote = 1;
			if (!$this->ALLOW_VOTE_FOR_OWNER && $_is_poll_owner) {
				$_allow_vote = 0;
			}

			// Check for errors
			if (!common()->_error_exists() && $_allow_vote) {
				foreach ((array)$_POST["choice"] as $k => $choice) {
					if (!isset($choices[$choice])) {
						continue;
					}
					db()->INSERT("poll_votes", array(
						"poll_id"	=> intval($poll_info["id"]),
						"user_id"	=> intval($this->USER_ID),
						"date"		=> time(),
						"value"		=> _es($choice),
					));
					db()->_add_shutdown_query(
						"UPDATE `".db('polls')."` SET `votes` = ( 
							SELECT COUNT(`id`) FROM `".db('poll_votes')."` WHERE `poll_id` = ".intval($poll_info["id"])."
						) 
						WHERE `id` = ".intval($poll_info["id"])
					);
				}
			}
			// For displaying results for common poll just voted by user
			if ($params["for_widgets"]) {
  				$_SESSION["_just_voted_poll"] = $_POST["poll_id"];
			}
			return js_redirect($RETURN_PATH);
		}

		$poll_info["question"] = trim($poll_info["question"]);
		if (substr($poll_info["question"], -1, 1) != "?") {
			$poll_info["question"] .= "?";
		}
		// Process main template
		$replace = array(
			"form_action"	=> $FORM_ACTION,
			"error_message"	=> _e(),
			"question"		=> _prepare_html(_ucfirst($poll_info["question"])),
			"choices"		=> $choices,
			"num_votes"		=> intval($poll_info["votes"]),
			"add_date"		=> _format_date($poll_info["add_date"], "long"),
			"results_link"	=> process_url($RESULTS_LINK),
			"poll_id"		=> intval($poll_info["id"]),
			"is_owner"		=> $params["is_owner"],
			"delete_url"	=> process_url($params["delete_url"]),
			"multiple"		=> $poll_info["multiple"],
		);
		return tpl()->parse($STPL_NAME_MAIN, $replace);
	}

	/**
	* Display vote results
	*/
	function view ($params = array(), $poll_info = array()) {
		// Get params
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"])		? intval($params["object_id"]) : intval($_GET["id"]);
		$STPL_NAME_VIEW = !empty($params["stpl_view"])		? $params["stpl_view"] : "poll/view";

		$POLL_ID 		= !empty($params["poll_id"])		? $params["poll_id"] : intval($_GET["id"]);

		if (!empty($params["poll_id"])) {
			$POLL_ID = intval($params["poll_id"]);
		}
		// Clear session data
		if (!empty($_SESSION["_just_voted_poll"])) {
			$POLL_ID = intval($_SESSION["_just_voted_poll"]);
			unset ($_SESSION["_just_voted_poll"]);
		}

		// Auto-find poll id
		if ($params["object_name"] && $params["object_id"] && !$params["poll_id"]) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `object_name`='"._es($params["object_name"])."' AND `object_id`=".intval($params["object_id"]). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
			$POLL_ID = $poll_info["id"];
		}
		// Currently only for members (for guests display)
		if (empty($this->USER_ID) && !$this->ALLOW_VIEW_FOR_GUESTS) {
			return !$params["silent"] ? _error_need_login() : false;
		}
		// Get poll info
		if (!empty($POLL_ID) && empty($poll_info)) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `id`=".intval($POLL_ID). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
		}
		if (empty($poll_info)) {
			$poll_info = db()->query_fetch(
				"SELECT * 
				FROM `".db('polls')."` 
				WHERE `object_name`='"._es($OBJECT_NAME)."' 
					AND `object_id`=".intval($OBJECT_ID). 
					($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : "")
			);
		}
		if (empty($poll_info)) {
			return !$params["silent"] ? _e(t("No such poll!")) : false;
		}
		// Prepare choices
		foreach ((array)explode("\n", str_replace("\r", "", $poll_info["choices"])) as $_id => $_text) {
			$choices[++$i] = array(
				"id"	=> intval($i),
				"text"	=> _prepare_html(_ucfirst($_text)),
			);
		}
		$max_choice_votes = 0;
		$num_votes = array();
		// Prepare votes
		$Q = db()->query("SELECT `value`, COUNT(`id`) AS `num` FROM `".db('poll_votes')."` WHERE `poll_id` = ".intval($poll_info["id"])." GROUP BY `value`");
		while ($A = db()->fetch_assoc($Q)) {
			$num_votes[$A["value"]] = $A["num"];
			// get max number of votes for the current choice
			if ($max_choice_votes < $A["num"]) {
				$max_choice_votes = $A["num"];
			}
		}
		$total_votes = array_sum($num_votes);
		// Prepare results
		$results = array();
		foreach ((array)$choices as $_id => $_info) {
			$_choice_votes	= intval($num_votes[$_id]);
			$_cur_percents	= $total_votes ? $_choice_votes / $total_votes * 100 : 0;
			$_cur_px		= floor($this->VIEW_MAX_WIDTH * $_cur_percents / 100);
			$results[$_id] = array(
				"id"		=> intval($_id),
				"text"		=> _prepare_html($_info["text"]),
				"num"		=> intval($_choice_votes),
				"percents"	=> round($_cur_percents, 2),
				"width"		=> intval($_cur_px),
				"color"		=> _prepare_html($this->VIEW_COLORS[$_id]),
			);
		}

		$poll_info["question"] = trim($poll_info["question"]);
		if (substr($poll_info["question"], -1, 1) != "?") {
			$poll_info["question"] .= "?";
		}
		// Process main template
		$replace = array(
			"question"		=> _prepare_html(_ucfirst($poll_info["question"])),
			"num_votes"		=> intval($poll_info["votes"]),
			"add_date"		=> _format_date($poll_info["add_date"], "long"),
			"results"		=> $results,
			"total_votes"	=> intval($total_votes),
			"delete_url"	=> process_url($params["delete_url"]),
			"is_owner"		=> $params["is_owner"],
		);
		return tpl()->parse($STPL_NAME_VIEW, $replace);
	}

	/**
	* Force display results for owner
	*/
	function owner_view () {
		$body = "<br /><br />\n<style type='text/css'>body{min-width:400px;}</style>\n".$this->view();
		echo common()->show_empty_page($body, array(
				"full_width" 	=> 1,
				"title"			=> "Poll",
				"close_button"	=> 1,
			));
	}

	/**
	* Create new poll
	*/
	function create ($params = array()) {
		if (!empty($_POST)) {
			$this->_create($params);
		}
	}

	/**
	* Create new poll
	*/
	function _create ($params = array()) {
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"])		? intval($params["object_id"]) : intval($_GET["id"]);
		$IS_COMMON		= !empty($params["common"])			? 1 : 0;


		// Display create form
		if (empty($_POST)) {
			// Show form for create poll
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=create_poll&id=".$_GET["id"],
				"return_action"		=> $_GET["page"],
				"allow_multiple"	=> $this->ALLOW_MULTI_CHOISES,
				"dynamic_que_adding"=> $this->DYNAMIC_QUE_ADDING,
				"max_choices"		=> $this->MAX_CHOICES,
				"object_name"		=> $OBJECT_NAME,
			);
			return tpl()->parse("poll/create_poll_form", $replace);
		}

		// Dynamic choices adding allowed
		if (!empty($_POST["choices_dyn"])) {
			$_POST["poll_choices"] = implode("\n", $_POST["choices_dyn"]);
		}

		// Prepare choices
		$choices = array();
		foreach (explode("\n", str_replace("\r", "", $_POST["poll_choices"])) as $_id => $_text) {
			if (!strlen($_text)) {
				continue;
			}
			if ($i > $this->MAX_CHOICES) {
				break;
			}
			$choices[++$i] = $_text;
		}
		if (empty($choices) || count($choices) < 2) {
			return false;
		}

		$exist_poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `user_id` !=0 AND `object_name`='".$OBJECT_NAME."' AND `object_id`=".$OBJECT_ID);
		if (!empty($exist_poll_info)) {
			return _e("Poll for this object already exists!");
		}
		if (!$this->ALLOW_MULTI_CHOISES && $_POST["allow_multiple"] == 1) {
			unset($_POST["multiple"]);
		}
		// Do insert record into db
		db()->INSERT("polls", array(
			"object_name"	=> _es($OBJECT_NAME),
			"object_id"		=> intval($OBJECT_ID),
			"user_id"		=> $IS_COMMON ? 0 : intval($this->USER_ID),
			"question"		=> _es($_POST["poll_question"]),
			"add_date"		=> time(),
			"choices"		=> _es(implode("\n", $choices)),
			"active"		=> 1,
			"multiple"		=> $_POST["allow_multiple"],
		));

		return !$params["silent"] ? js_redirect("./?object=".$_GET["object"]."&action=".$params["return_action"]."&id=".$OBJECT_ID) : "";
	}

	/**
	* Delete poll
	*/
	function delete ($params = array()) {
		$POLL_ID = intval($_GET["id"]);
		// Currently only for members (for guests display)
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		if (!empty($POLL_ID)) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `id`=".intval($POLL_ID). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
		}
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"])		? intval($params["object_id"]) : intval($_GET["id"]);
		// Check required params
		if (empty($poll_info) && (empty($OBJECT_NAME) || empty($OBJECT_ID))) {
			return _e(t("Missing required params for poll!"));
		}
		// Get poll info
		if (empty($poll_info)) {
			$poll_info = db()->query_fetch("SELECT * FROM `".db('polls')."` WHERE `object_name`='"._es($OBJECT_NAME)."' AND `object_id`=".intval($OBJECT_ID). ($this->PROCESS_STATUS_FIELD ? " AND `active`='1' " : ""));
		}
		if (empty($poll_info)) {
			return _e(t("No such poll!"));
		}
		// Check owner
		if ($this->USER_ID != $poll_info["user_id"]) {
			return _e(t("Not your poll"));
		}
		db()->query("DELETE FROM `".db('poll_votes')."` WHERE `poll_id` = ".intval($poll_info["id"]));
		db()->query("DELETE FROM `".db('polls')."` WHERE `id` = ".intval($poll_info["id"]));
		// Return user back
		if (!$params["silent"]) {
			return js_redirect($_SERVER["HTTP_REFERER"], 1);
		}
	}

	/**
	* Shows poll block
	*/
	function _show_poll_block ($object_id, $object_name = "") {
		if (!$object_name) {
			$object_name = $_GET["object"];
		}
		$object_id = intval($object_id);

		// Check if poll exists for the given object
		$sql = "SELECT * FROM `".db('polls')."` WHERE `object_name`='"._es($object_name)."' AND `object_id`=".intval($object_id);
		$poll_info = db()->query_fetch($sql);
		$_is_poll_owner = ($this->USER_ID && $this->USER_ID == $poll_info["user_id"]) ? 1 : 0;
		// No poll yet, allow to add for "object_id" and "object_name" owner
		if (empty($poll_info)) {
			// Poll for the given object not exists. Prompt to create one if current user is owner of this object
			if ($this->USER_ID && $this->_is_object_owner ($object_id, $object_name)) {
				// Create poll
				$replace = array(
					"create_poll_url"	=> "./?object=".$object_name."&action=create_poll&id=".$object_id,
				);
				return tpl()->parse("poll/create_poll_link", $replace);
			}
		} else {
			$delete_url = ($_is_poll_owner == 1) ? "./?object=".$object_name."&action=delete_poll&id=".$poll_info["id"] : "";
		}

		if ($_is_poll_owner && !$this->ALLOW_VOTE_FOR_OWNER) {
			return $this->view($params);
		}

		return $this->show(array(
			"poll_id"				=> $poll_info["id"],
			"return_action" 		=> $_GET["action"], 
			"delete_url"			=> process_url($delete_url),
			"is_owner"				=> intval((bool)$_is_poll_owner),
			"results_link"			=> "./?object=".$object_name."&action=view_poll_results&id=".$poll_info["id"],
		));
	}

	/**
	* Check if current user is object owner
	*/
	function _is_object_owner ($object_id, $object_name = "") {
		if (!$this->USER_ID || !$object_id || !$object_name) {
			return false;
		}
		$object_id = intval($object_id);
		// Check owner
		if ($object_name == "gallery") {
			$sql = "SELECT `id`, `user_id` FROM `".db('gallery_photos')."` WHERE `id`=".$object_id;
		} elseif ($object_name == "blog") {
			$sql = "SELECT `id`, `user_id` FROM `".db('blog_posts')."` WHERE `id`=".$object_id;
		} elseif ($object_name == "articles") {
			$sql = "SELECT `id`, `user_id` FROM `".db('articles_texts')."` WHERE `id`=".$object_id;
		} elseif ($object_name == "forum") {
			$sql = "SELECT `id`, `user_id` FROM `".db('forum_posts')."` WHERE `id`=".$object_id;
		}
		$B = db()->query_fetch($sql);
		if ($this->USER_ID && $this->USER_ID == $B["user_id"]) {
			$is_owner = 1;
		} else {
			$is_owner = 0;
		}
		return $is_owner;
	}

	/**
	* Common poll
	*/
	function _widget_public ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0);
		}
		if (isset($_SESSION["_just_voted_poll"])) { 
			$poll_id = $_SESSION["_just_voted_poll"];
			return $this->view(array(
				"poll_id" 		=> $poll_id, 
				"stpl_main" 	=> "poll/widgets_main",
				"stpl_view"		=> "poll/widget_view",
				"for_widgets"	=> 1,
			));
		} else {
			$P = db()->query_fetch("SELECT `id` FROM `".db('polls')."` WHERE `user_id`=0 AND `active`=1 ORDER BY RAND() LIMIT 1");
			return $this->show(array(
				"poll_id" 		=> $P["id"], 
				"stpl_main" 	=> "poll/widgets_main",
				"stpl_view"		=> "poll/widget_view",
				"for_widgets"	=> 1,
				"form_action"	=> "./?object=poll",
			));
		}
	}
}
