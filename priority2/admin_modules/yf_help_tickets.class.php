<?php

//-----------------------------------------------------------------------------
// Help tickets handler
class yf_help_tickets {

	/** @var */
	public $TICKET_DELIM		= "jq";
	/** @var Filter on/off */
	public $USE_FILTER			= true;
	/** @var Filter on/off */
	public $DEF_PER_PAGE		= 50;
	/** @var string */
	public $DEF_VIEW_STATUS	= "";
	/** @var bool */
	public $ADD_ADMIN_NAME		= true;

	//-----------------------------------------------------------------------------
	// Constructor
	function _init () {
		main()->USER_ID = intval($_SESSION["admin_id"]);
		// Array of boxes
		$this->_boxes = array(
			"admin_priority"	=> 'select_box("admin_priority",$this->_priorities,		$selected, "", 2, "", false)',
			"user_priority"		=> 'select_box("user_priority",	$this->_priorities,		$selected, "", 2, "", false)',
			"category_id"		=> 'select_box("category_id",	$this->_help_cats,		$selected, "", 2, "", false)',
			"status"			=> 'select_box("status",		$this->_ticket_statuses,$selected, "", 2, "", false)',
			"assigned_to"		=> 'select_box("assigned_to",	$this->_admins_list2,	$selected, "", 2, "", false)',
		);
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Priorities array
		$this->_priorities = array(
			4	=> t("Urgent"),
			3	=> t("High"),
			2	=> t("Medium"),
			1	=> t("Low"),
		);
		// Prepare categories
		$this->CATS_OBJ		= main()->init_class("cats", "classes/");
		$this->CATS_OBJ->_default_cats_block = "help_cats";
		$this->_help_cats	= $this->CATS_OBJ->_prepare_for_box("", 0);
		// Array of statuses
		$this->_ticket_statuses = array(
			"new"		=> t("new"),
			"read"		=> t("read"),
			"open"		=> t("open"),
			"closed"	=> t("closed"),
			"not_closed"=> t("not_closed"),
		);
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
		foreach ((array)$this->_sites_info->info as $site_id => $site_info) {
			$this->_sites_names[$site_id] = $site_info["name"];
		}
		// Get available admin groups
		$this->_admin_groups	= main()->get_data("admin_groups");
		// Get available admin users who have access to this module (currently administrators and support)
		$Q = db()->query("SELECT * FROM ".db('admin')." WHERE group IN(1,4) ORDER BY `group` ASC, first_name ASC, last_name ASC");
		while ($A = db()->fetch_assoc($Q2)) {
			$this->_admins_list[$A["id"]] = _prepare_html($A["first_name"]." ".$A["last_name"]." (".$this->_admin_groups[$A["group"]].")");
		}
		$this->_admins_list2[" "] = t("-- All --");
		foreach ((array)$this->_admins_list as $k => $v) {
			$this->_admins_list2[$k] = $v;
		}
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
		// Do save filter if needed
		if (!empty($_GET["email"])) {
			$_REQUEST["email"] = $_GET["email"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		// Prepare SQL
		$sql = "SELECT * FROM ".db('help_tickets')." ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY opened_date DESC ";

		$per_page = $this->DEF_PER_PAGE;
		if ($this->USE_FILTER && $_SESSION[$this->_filter_name]["per_page"]) {
			$per_page = $_SESSION[$this->_filter_name]["per_page"];
		}
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "./?object=".$_GET["object"], "", $per_page);
		// Get ids
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$tickets_array[$A["id"]]= $A;
			$tickets_ids[$A["id"]]	= $A["id"];
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get last answers for each ticket
		if (!empty($tickets_ids)) {
			$OBJECT_NAME	= "help";
			// Get data from db
			$Q = db()->query(
				"SELECT * 
				FROM ".db('comments')." 
				WHERE object_id IN(".implode(",", $tickets_ids).") 
					AND object_name='"._es($OBJECT_NAME)."' 
				ORDER BY add_date ASC"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$last_comments[$A["object_id"]]	= $A;
				if (!empty($A["user_id"])) {
					$users_ids[$A["user_id"]]	= $A["user_id"];
				}
			}
			// COunt number of answers
			$Q = db()->query(
				"SELECT object_id, COUNT(*) AS num_answers
				FROM ".db('comments')." 
				WHERE object_id IN(".implode(",", $tickets_ids).") 
					AND object_name='"._es($OBJECT_NAME)."' 
				GROUP BY object_id"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$num_answers[$A["object_id"]]	= $A["num_answers"];
			}
		}
		// Get users infos
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT id,name,nick,login,password,email FROM ".db('user')." WHERE id IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_infos[$A["id"]] = $A;
		}
		// Process users
		foreach ((array) $tickets_ids as $cur_ticket_id) {
			$A = $tickets_array[$cur_ticket_id];
			$last_answer_info = $last_comments[$A["id"]];
			$last_user_id	= $last_answer_info["user_id"];
			// Prepare last answered user name
			$last_user_name = $last_answer_info["user_name"];
			if (!empty($last_answer_info) && !empty($last_user_id) && empty($last_user_name)) {
				$last_user_name = _display_name($users_infos[$last_user_id]);
			}
			// Prepare template
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"ticket_id"			=> intval($A["id"]),
				"user_id"			=> intval($A["user_id"]),
				"user_name"			=> _prepare_html($A["name"]),
				"user_login"		=> _prepare_html($A["login"]),
				"user_password"		=> _prepare_html($A["password"]),								
				"user_email"		=> _prepare_html($A["email"]),
				"account_link"		=> $A["user_id"] ? "./?object=account&user_id=".$A["user_id"] : "",
				"subject"			=> _prepare_html($A["subject"]),
				"message"			=> _prepare_html(substr($A["message"], 0, 200)),
				"opened_date"		=> _format_date($A["opened_date"], "long"),
				"closed_date"		=> $A["closed_date"] ? _format_date($A["closed_date"], "long") : "",
				"user_priority"		=> _prepare_html($this->_priorities[$A["user_priority"]]),
				"admin_priority"	=> _prepare_html($this->_priorities[$A["admin_priority"]]),
				"status"			=> _prepare_html($A["status"]),
				"category_name"		=> _prepare_html($this->_help_cats[$A["category_id"]]),
				"num_replies"		=> intval($num_answers[$A["id"]]),
				"edit_link"			=> "./?object=".$_GET["object"]."&action=edit&id=".intval($A["id"])._add_get(array("page")),
				"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&id=".intval($A["id"])._add_get(array("page")),
				"last_user_link"	=> $last_user_id ? "./?object=account&user_id=".$last_user_id : "",
				"last_user_name"	=> _prepare_html($last_user_name),
				"assigned_to_name"	=> $A["assigned_to"] ? _prepare_html($this->_admins_list[$A["assigned_to"]]) : "",
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process main template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"add_link"		=> "./?object=".$_GET["object"]."&action=add"._add_get(array("page")),
			"ajax_link"		=> process_url("./?object=".$_GET["object"]."&action=ajax_ticket_source"),
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_actions",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Edit record
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("No id");
		}
		// Try to get record info
		$ticket_info = db()->query_fetch("SELECT * FROM ".db('help_tickets')." WHERE id=".intval($_GET["id"]));
		if (empty($ticket_info)) {
			return _e("No such ticket");
		}
		// Update ticket status to "read" if it's status was "new"
		if ($ticket_info["status"] == "new") {
			db()->query("UPDATE ".db('help_tickets')." SET status = 'read' WHERE id=".intval($ticket_info["id"]));
			$ticket_info["status"] = "read";
		}
		// Try to get given user info
		$user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE ".(!empty($ticket_info["user_id"]) ? "id=".intval($ticket_info["user_id"]) : "email='"._es($ticket_info["email"])."'"));
		// Allow only to set "open" or "closed" status on save
		unset($this->_ticket_statuses["new"]);
		unset($this->_ticket_statuses["read"]);
		// Check posted data and save
		if (count($_POST) > 0) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("help_tickets", array(
					"admin_comments"	=> _es($_POST["admin_comments"]),
					"admin_priority"	=> intval($_POST["admin_priority"]),
					"closed_date"		=> intval($_POST["status"] == "closed" && $ticket_info["status"] != $_POST["status"] ? time() : $ticket_info["closed_date"]),
					"status"			=> _es($_POST["status"]),
					"assigned_to"		=> intval($_POST["assigned_to"]),
				), "id=".intval($_GET["id"]));
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		foreach ((array)$ticket_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		$TICKET_ID = $ticket_info["ticket_key"];
		// Prepare related urls
		$related_urls = array();
		if (!empty($ticket_info["urls"])) {
			$tmp_array = explode(" ", str_replace(array("\r","\n","\t"), " ", $ticket_info["urls"]));
			foreach ((array)$tmp_array as $v) {
				if (empty($v)) continue;
				$related_urls[] = array(
					"cur_url"	=> _prepare_html($v),
				);
			}
		}
		// Display form
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"error_message"		=> _e(),
			"user_id"			=> _prepare_html($ticket_info["user_id"]),
			"user_name"			=> _prepare_html($ticket_info["name"]),
			"user_email"		=> _prepare_html($ticket_info["email"]),
			"user_login"		=> _prepare_html($user_info["login"]),
			"user_password"		=> _prepare_html($user_info["password"]),
			"profile_link"		=> $ticket_info["user_id"] ? "./?object=account&user_id=".$ticket_info["user_id"] : "",
			"cats_box"			=> $this->_box("category_id", $DATA["category_id"]),
			"status_box"		=> $this->_box("status", $DATA["status"]),
			"admin_priority_box"=> $this->_box("admin_priority", $DATA["admin_priority"]),
			"user_priority_box"	=> $this->_box("user_priority", $DATA["user_priority"]),
			"assigned_to_box"	=> $this->_box("assigned_to", $DATA["assigned_to"]),
			"user_priority"		=> _prepare_html($this->_priorities[$DATA["user_priority"]]),
			"category_name"		=> _prepare_html($this->_help_cats[$DATA["category_id"]]),
			"subject"			=> _prepare_html($DATA["subject"], 0),
			"message"			=> nl2br(_prepare_html($DATA["message"], 0)),
			"admin_comments"	=> nl2br(_prepare_html($DATA["admin_comments"], 0)),
			"opened_date"		=> _format_date($DATA["opened_date"], "long"),
			"closed_date"		=> $DATA["closed_date"] ? _format_date($DATA["closed_date"], "long") : "",
			"ticket_id"			=> _prepare_html($TICKET_ID),
			"urls"				=> !empty($related_urls) ? $related_urls : "",
			"search_email_link"	=> "./?object=".$_GET["object"]."&action=show&email="._prepare_html($ticket_info["email"]),
			"answers"			=> $this->_view_answers(),
			"back_url"			=> "./?object=".$_GET["object"],
			"activate_link"		=> !empty($user_info) ? "./?object=".$_GET["object"]."&action=activate_account&ticket_id=".$ticket_info["id"] : "",
			"account_active"	=> !empty($user_info) ? intval($user_info["active"]) : "",
			"user_agent"		=> _prepare_html($ticket_info["user_agent"]),
			"ip"				=> _prepare_html($ticket_info["ip"]),
			"cookies_enabled"	=> $ticket_info["cookies_enabled"] ? 1 : 0,
			"ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($user_info["id"])),
			"site_id"			=> intval($ticket_info["site_id"]),
			"site_name"			=> $ticket_info["site_id"] ? $this->_sites_names[$ticket_info["site_id"]] : "",
			"referer"			=> $ticket_info["referer"],
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	//-----------------------------------------------------------------------------
	// Activate user's account
	function activate_account () {
		$_GET["ticket_id"]		= intval($_GET["ticket_id"]);
		if (empty($_GET["ticket_id"])) {
			echo common()->show_empty_page("Missing ticket_id");
			return false;
		}
		// Try to get record info
		$ticket_info = db()->query_fetch("SELECT * FROM ".db('help_tickets')." WHERE id=".intval($_GET["ticket_id"]));
		if (empty($ticket_info)) {
			echo common()->show_empty_page("No such ticket");
			return false;
		}
		// Try to get given user info
		$user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE ".(!empty($ticket_info["user_id"]) ? "id=".intval($ticket_info["user_id"]) : "email='"._es($ticket_info["email"])."'"));
		if (empty($user_info["id"])) {
			echo common()->show_empty_page("Cant find user with such data");
			return false;
		}
		$success = 0;
		// Do activate user's account
		db()->query("UPDATE ".db('user')." SET active='1' WHERE id=".intval($user_info["id"]));
		// Prepare email
		$replace = array(
			"user_name"		=> _prepare_html(_display_name($user_info)),
			"user_login"	=> _prepare_html($user_info["login"]),
			"user_password"	=> _prepare_html($user_info["password"]),
		);
		$message = tpl()->parse($_GET["object"]."/email_on_auto_activate", $replace);
		// Do send mail
		$send_result = common()->send_mail(SITE_ADMIN_EMAIL_ADV, "Admin ".SITE_ADVERT_NAME, $user_info["email"], $user_info["email"], "Help ticket answer", $message, nl2br($message));
		// Close ticket
		db()->query("UPDATE ".db('help_tickets')." SET status='closed' WHERE id=".intval($ticket_info["id"]));
		// Do not close ticket if we have troubles with sending email
		if ($send_result) {
			$success = 1;
		} else {
			// comment that we have trouble with sending email
			$messqge = "Error with sending confirmation email to user";
		}
		// Add comment
		db()->INSERT("comments", array(
			"object_name"		=> _es("help"),
			"object_id"			=> intval($ticket_info["id"]),
			"user_id"			=> 0,
			"user_name"			=> _es("Admin: ".$admin_name),
			"text" 				=> _es($message),
			"add_date"			=> time(),
			"active"			=> 1,
		));
		// Get output content
		$replace = array(
			"success"	=> $success,
		);
		$body = tpl()->parse($_GET["object"]."/activate_account", $replace);
		echo common()->show_empty_page($body);
	}

	//-----------------------------------------------------------------------------
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		$OBJECT_NAME	= "help";
		if (empty($_GET["id"])) {
			return _e("No id");
		}
		$ticket_info = db()->query_fetch("SELECT * FROM ".db('help_tickets')." WHERE id=".intval($_GET["id"]));
		// Remove activity points
		common()->_remove_activity_points($ticket_info["user_id"], "bug_report", $_GET["id"]);
		// Do delete record
		db()->query("DELETE FROM ".db('help_tickets')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		db()->query("DELETE FROM ".db('comments')." WHERE object_id=".intval($_GET["id"])." AND object_name='"._es($OBJECT_NAME)."'");
		// Return user back
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Get number of answers for the given tickets
	*
	* @access	public
	* @return	string
	*/
	function _get_num_answers ($OBJECTS_IDS = array()) {
		$OBJECT_NAME	= "help";
		unset($OBJECTS_IDS[""]);
		if (empty($OBJECTS_IDS)) {
			return false;
		}
		// Do get number of ids from db
		$Q = db()->query("SELECT COUNT(id) AS num,object_id FROM ".db('comments')." WHERE object_id IN(".implode(",", $OBJECTS_IDS).") AND object_name='"._es($OBJECT_NAME)."' GROUP BY object_id");
		while ($A = db()->fetch_assoc($Q)) $num_comments_by_object_id[$A["object_id"]] = $A["num"];
		// Do return result
		return $num_comments_by_object_id;
	}

	/**
	* View answers for the given ticket
	*
	* @access	public
	* @return	string
	*/
	function _view_answers () {
		$OBJECT_ID		= $_GET["id"];
		$OBJECT_NAME	= "help";
		// Prepare SQL
		$sql		= "SELECT * FROM ".db('comments')." WHERE object_name='"._es($OBJECT_NAME)."' AND object_id=".intval($OBJECT_ID);
		$order_sql	= " ORDER BY add_date DESC";
		// Connect pager
		list($add_sql, $pages, $total) = common()->divide_pages(str_replace("SELECT *", "SELECT id", $sql)/*, $PAGER_PATH, null, $this->NUM_PER_PAGE*/);
		// Process items
		$Q = db()->query($sql.$order_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$answers_array[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Try to get users names
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM ".db('user')." WHERE id IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$users_names[$A["id"]] = _display_name($A);
				$GLOBALS['verified_photos'][$A["id"]] = $A["photo_verified"];
			}
		}
		// Process answers items
		foreach ((array)$answers_array as $answer_info) {
			// Prepare answer text
			$answer_info["text"] = str_replace(array("\\\\","\\'","\\\""), array("\\","'","\""), $answer_info["text"]);
			$replace2 = array(
				"need_div"				=> intval($i > 0),
				"bg_class"				=> !(++$i % 2) ? "bg1" : "bg2",
				"user_name"				=> _prepare_html(!empty($answer_info["user_id"]) ? $users_names[$answer_info["user_id"]] : $answer_info["user_name"]),
				"user_avatar"			=> $answer_info["user_id"] ? _show_avatar($answer_info["user_id"], $users_names[$answer_info["user_id"]], 1, 0) : "",
				"user_profile_link"		=> $answer_info["user_id"] ? "./?object=account&user_id=".$answer_info["user_id"] : "",
				"user_email_link"		=> _email_link($answer_info["user_id"]),
				"add_date"				=> _format_date($answer_info["add_date"], "long"),
				"answer_text"			=> $this->_format_text($answer_info["text"]),
				"edit_link"				=> "./?object=".$_GET["object"]."&action=edit_answer&id=".$answer_info["id"]._add_get(array("page")),
				"delete_link"			=> "./?object=".$_GET["object"]."&action=delete_answer&id=".$answer_info["id"]._add_get(array("page")),
				"reput_text"			=> is_object($REPUT_OBJ) ? $REPUT_OBJ->_show_for_user($answer_info["user_id"], $users_reput_info[$answer_info["user_id"]]) : "",
			);
			$items .= tpl()->parse($_GET["object"]."/answers_item", $replace2);
		}
		// Process main template
		$replace = array(
			"answers"			=> $items,
			"pages"				=> $pages,
			"num_answers"		=> intval($total),
			"add_answer_form"	=> $this->add_answer(),
		);
		return tpl()->parse($_GET["object"]."/answers_main", $replace);
	}

	/**
	* Form to add answers
	*
	* @access	public
	* @return	string
	*/
	function add_answer () {
		$_GET["id"] = intval($_GET["id"]);
		$OBJECT_ID		= $_GET["id"];
		$OBJECT_NAME	= "help";
		// Try to get record info
		$ticket_info = db()->query_fetch("SELECT * FROM ".db('help_tickets')." WHERE id=".intval($_GET["id"]));
		if (empty($ticket_info)) {
			return _e("No such ticket");
		}
		// Do add answer
		if (count($_POST) > 0) {
			// Check for errors
			if (!common()->_error_exists()) {
				if (empty($_POST["text"])) {
					_re("answer text required");
				}
			}
			// Prepare ticket id
			$TICKET_ID = $ticket_info["ticket_key"];
			// Do get current admin info
			$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($_SESSION["admin_id"]));
			$admin_name = $admin_info["first_name"]." ".$admin_info["last_name"];
			// Check for errors
			if (!common()->_error_exists()) {
				// Do insert record
				db()->INSERT("comments", array(
					"object_name"		=> _es($OBJECT_NAME),
					"object_id"			=> intval($OBJECT_ID),
					"user_id"			=> 0,
					"user_name"			=> _es("Admin: ".$admin_name),
					"text" 				=> _es($_POST["text"]),
					"add_date"			=> time(),
					"active"			=> 1,
				));
				$RECORD_ID = db()->INSERT_ID();
			}
			// Add activity points for registered user if needed
			if (!common()->_error_exists()) {
				if (!empty($ticket_info["user_id"]) && !empty($_POST["add_activity"])) {
					common()->_add_activity_points($ticket_info["user_id"], "bug_report", 1000, $RECORD_ID);
				}
			}
			// Try to send mail to user
			if (!common()->_error_exists()) {
				// Get first site info
				if (is_array($this->_sites_info->info))	{
					$FIRST_SITE_INFO = array_shift($this->_sites_info->info);
				}
				if (!common()->_error_exists()) {
					$replace = array(
						"name"				=> _prepare_html($ticket_info["name"]),
						"site_name"			=> _prepare_html($FIRST_SITE_INFO["name"]),
						"author_name"		=> _prepare_html($this->ADD_ADMIN_NAME ? SITE_ADVERT_NAME." Admin ".$admin_name : SITE_ADMIN_NAME),
						"text"				=> _prepare_html($_POST["text"]),
						"ticket_id"			=> _prepare_html($TICKET_ID),
						"ticket_url"		=> process_url("./?object=help&action=view_answers&id=".$TICKET_ID, 1, $ticket_info["site_id"]),
						"request_subject"	=> _prepare_html($ticket_info["subject"]),
						"request_message"	=> _prepare_html($ticket_info["message"]),
					);
					$text		= tpl()->parse($_GET["object"]."/email_to_user", $replace);
					$email_from	= SITE_ADMIN_EMAIL;
					$name_from	= $this->ADD_ADMIN_NAME ? SITE_ADVERT_NAME." Admin ".$admin_name : SITE_ADMIN_NAME;
					$email_to	= $ticket_info["email"];
					$name_to	= _prepare_html($ticket_info["name"]);
					$subject	= "Response to support ticket #".$TICKET_ID;
					$result		= common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
					// Check if email is sent - else show error
					if (!$result) {
						//
					}
					if (defined("ADMIN_DUPLICATE_EMAIL") && ADMIN_DUPLICATE_EMAIL) {
						$email_to	= ADMIN_DUPLICATE_EMAIL;
						$subject	= "FWD: Response to support ticket #".$TICKET_ID;
						common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
					}
				}
			}
			if (!common()->_error_exists()) {
				// Do close ticket if needed
				if (!empty($_POST["close_ticket"])) {
					db()->UPDATE("help_tickets", array("status" => "closed"), "id=".intval($_GET["id"]));
					// Return user to tickets list
					return js_redirect("./?object=".$_GET["object"]);
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".$_GET["id"]);
			}
		}
		$error_message = _e();
		// Display form
		if (empty($_POST["go"]) || !empty($error_message)) {
			$replace = array(
				"answer_form_action"=> "./?object=".$_GET["object"]."&action=".__FUNCTION__."&id=".$_GET["id"],
				"error_message"		=> $error_message,
				"text"				=> _prepare_html($_POST["text"]),
				"object_id"			=> intval($OBJECT_ID),
				"bb_codes_block"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "text")) : "",
				"for_edit"			=> 0,
			);
			$body = tpl()->parse($_GET["object"]."/answers_edit_form", $replace);
		}
		return $body;
	}

	/**
	* Do edit answer
	*
	* @access	public
	* @return	string
	*/
	function edit_answer () {
		$_GET["id"] = intval($_GET["id"]);
		$OBJECT_ID		= $_GET["id"];
		$OBJECT_NAME	= "help";
		// Try to get given comment info
		$answer_info = db()->query_fetch("SELECT * FROM ".db('comments')." WHERE id=".intval($_GET["id"]));
		if (empty($answer_info["id"])) {
			_re("No such answer!");
			return _e();
		}
		// Check posted data and save
		if (count($_POST) > 0) {
			if (empty($_POST["text"])) {
				_re("answer text required");
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Do update record
				db()->UPDATE("comments", array(
					"text" 			=> _es($_POST["text"]),
				), "id=".intval($answer_info["id"]));
				// Return user back
				return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".$answer_info["object_id"], false);
			}
		} else {
			$_POST["text"] = $answer_info["text"];
		}
		$error_message = _e();
		// Show form
		if (empty($_POST["go"]) || !empty($error_message)) {
			$replace = array(
				"answer_form_action"=> "./?object=".$_GET["object"]."&action=".__FUNCTION__."&id=".$answer_info["id"],
				"error_message"		=> $error_message,
				"user_id"			=> intval(main()->USER_ID),
				"user_name"			=> _prepare_html(_display_name($user_info)),
				"user_avatar"		=> _show_avatar($answer_info["user_id"], $user_info, 1, 1),
				"user_profile_link"	=> $answer_info["user_id"] ? "./?object=account&user_id=".$answer_info["user_id"] : "",
				"user_email_link"	=> _email_link($answer_info["user_id"]),
				"text"				=> _prepare_html($_POST["text"]),
				"back_url"			=> "./?object=".$_GET["object"]."&action=edit&id=".$answer_info["id"],
				"bb_codes_block"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "text")) : "",
				"for_edit"			=> 1,
			);
			$body = tpl()->parse($_GET["object"]."/answers_edit_form", $replace);
		}
		return $body;
	}

	/**
	* Do delete answer
	*
	* @access	public
	* @return	string
	*/
	function delete_answer () {
		$_GET["id"] = intval($_GET["id"]);
		$OBJECT_ID		= $_GET["id"];
		$OBJECT_NAME	= "help";
		// Try to get given answer info
		$answer_info = db()->query_fetch("SELECT * FROM ".db('comments')." WHERE id=".intval($_GET["id"]));
		if (empty($answer_info["id"])) {
			_re("No such answer!");
			return _e();
		}
		// Do delete answer
		db()->query("DELETE FROM ".db('comments')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".$answer_info["object_id"], false);
	}

	//-----------------------------------------------------------------------------
	// Return ticket text for AJAX
	function ajax_ticket_source () {
		main()->NO_GRAPHICS = true;
		$TICKET_ID = intval(substr($_REQUEST["id"], strlen("ticket_")));
		if (empty($TICKET_ID)) {
			echo _e("No id");
			return false;
		}
		// Try to get record info
		$ticket_info = db()->query_fetch("SELECT * FROM ".db('help_tickets')." WHERE id=".intval($TICKET_ID));
		if (empty($ticket_info)) {
			echo _e("No such ticket");
			return false;
		}
		$body = nl2br(_prepare_html($ticket_info["message"]));
		if (DEBUG_MODE) {
			$body .= "<hr class='clearfloat'>DEBUG INFO:\r\n";
			$body .= common()->_show_execution_time();
//			$body .= common()->show_debug_info();
		}
		echo $body;
	}

	//-----------------------------------------------------------------------------
	// Do mass actions with selected items
	function mass_actions () {
		$OBJECT_NAME	= "help";

		if (isset($_POST["delete"])) {
			$CURRENT_OPERATION = "delete";
		} elseif (isset($_POST["close"])) {
			$CURRENT_OPERATION = "close";
		} elseif (isset($_POST["activate"])) {
			$CURRENT_OPERATION = "activate";
		} elseif (isset($_POST["mass_reply"])) {
			$CURRENT_OPERATION = "mass_reply";
		}
		// Check if we determine current operation
		if (empty($CURRENT_OPERATION)) {
			return _e("Please select operation");
		}
		// Prepare ads ids
		foreach ((array)$_POST["items"] as $cur_id) {
			$cur_id = intval($cur_id);
			if (empty($cur_id)) {
				continue;
			}
			$items_ids[$cur_id] = $cur_id;
		}
		// Get tickets
		if (!empty($items_ids)) {
			$Q = db()->query("SELECT * FROM ".db('help_tickets')." WHERE id IN(".implode(",", $items_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$tickets_infos[$A["id"]]	= $A;
				$emails[$A["email"]]		= _es($A["email"]);
			}
		}
		// Get users details
		if (!empty($emails)) {
			$Q = db()->query("SELECT * FROM ".db('user')." WHERE email IN('".implode("','", $emails)."')");
			while ($A = db()->fetch_assoc($Q)) {
				$users_infos[$A["email"]] = $A;
			}
		}
		// Do get current admin info
		$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($_SESSION["admin_id"]));
		$admin_name = $admin_info["first_name"]." ".$admin_info["last_name"];
		// Switch between operation
		// ###########################################
		if ($CURRENT_OPERATION == "delete") {

			if (!empty($items_ids)) {
				db()->query("DELETE FROM ".db('help_tickets')." WHERE id IN(".implode(",",$items_ids).")");
			}

		// ###########################################
		} elseif ($CURRENT_OPERATION == "close") {

			if (!empty($items_ids)) {
				db()->query("UPDATE ".db('help_tickets')." SET status='closed' WHERE id IN(".implode(",",$items_ids).")");
			}

		// ###########################################
		} elseif ($CURRENT_OPERATION == "activate") {

			foreach ((array)$tickets_infos as $_id => $_ticket_info) {
				$user_info = $users_infos[$_ticket_info["email"]];
				if (empty($user_info) 
					|| $user_info["is_deleted"] == 1
				) {
					continue;
				}
				// Do activate user's account
				db()->query("UPDATE ".db('user')." SET active='1' WHERE id=".intval($user_info["id"]));
				// Prepare email
				$replace = array(
					"user_name"		=> _prepare_html(_display_name($user_info)),
					"user_login"	=> _prepare_html($user_info["login"]),
					"user_password"	=> _prepare_html($user_info["password"]),
				);
				$message = tpl()->parse($_GET["object"]."/email_on_auto_activate", $replace);
				// Do send mail
				$from_name = $this->ADD_ADMIN_NAME ? "Admin ".SITE_ADVERT_NAME : SITE_ADMIN_NAME;
				$send_result = common()->send_mail(SITE_ADMIN_EMAIL_ADV, $from_name, $_ticket_info["email"], $_ticket_info["email"], "Help ticket answer", $message, nl2br($message));
				// Close ticket
				db()->query("UPDATE ".db('help_tickets')." SET status='closed' WHERE id=".intval($_ticket_info["id"]));
				// Do not close ticket if we have troubles with sending email
				if ($send_result) {
				} else {
					// comment that we have trouble with sending email
					$messqge = "Error with sending confirmation email to user";
				}
				// Add comment
				db()->INSERT("comments", array(
					"object_name"		=> _es("help"),
					"object_id"			=> intval($_ticket_info["id"]),
					"user_id"			=> 0,
					"user_name"			=> _es("Admin: ".$admin_name),
					"text" 				=> _es($message),
					"add_date"			=> time(),
					"active"			=> 1,
				));
			}

		// ###########################################
		} elseif ($CURRENT_OPERATION == "mass_reply") {

			// Get first site info
			if (is_array($this->_sites_info->info))	{
				$FIRST_SITE_INFO = array_shift($this->_sites_info->info);
			}
			$processed_tickets_ids = array();
			// Process selected tickets
			foreach ((array)$tickets_infos as $_id => $_ticket_info) {
				// Prepare ticket id
				$TICKET_ID = $_ticket_info["ticket_key"];
// TODO: need to do something when text is empty
				// Prepare text to replay
				$replace_pairs = array(
					'%%user_name%%'		=> !empty($user_info) ? _display_name($user_info) : $_ticket_info["name"],
					'%%account_type%%'	=> !empty($user_info) ? $this->_account_types[$user_info["group"]] : "",
				);
				$PREPARED_TEXT = str_replace(
					array_keys($replace_pairs), 
					array_values($replace_pairs), 
					$_POST["reply_text"]
				);
				// Do insert record
				db()->INSERT("comments", array(
					"object_name"		=> _es($OBJECT_NAME),
					"object_id"			=> intval($_ticket_info["id"]),
					"user_id"			=> 0,
					"user_name"			=> _es("Admin: ".$admin_name),
					"text" 				=> _es($PREPARED_TEXT),
					"add_date"			=> time(),
					"active"			=> 1,
				));
				$RECORD_ID = db()->INSERT_ID();
				// Add activity points for registered user if needed
				if (!empty($_ticket_info["user_id"])) {
					common()->_add_activity_points($_ticket_info["user_id"], "bug_report", 1000, $RECORD_ID);
				}
				$replace = array(
					"name"				=> _prepare_html($_ticket_info["name"]),
					"site_name"			=> _prepare_html($FIRST_SITE_INFO["name"]),
					"author_name"		=> _prepare_html($this->ADD_ADMIN_NAME ? SITE_ADVERT_NAME." Admin ".$admin_name : SITE_ADMIN_NAME),
					"text"				=> _prepare_html($PREPARED_TEXT),
					"ticket_id"			=> _prepare_html($TICKET_ID),
					"ticket_url"		=> process_url("./?object=help&action=view_answers&id=".$TICKET_ID, 1, $_ticket_info["site_id"]),
					"request_subject"	=> _prepare_html($_ticket_info["subject"]),
					"request_message"	=> _prepare_html($_ticket_info["message"]),
				);
				$text		= tpl()->parse($_GET["object"]."/email_to_user", $replace);
				$email_from	= SITE_ADMIN_EMAIL;
				$name_from	= $this->ADD_ADMIN_NAME ? SITE_ADVERT_NAME." Admin ".$admin_name : SITE_ADMIN_NAME;
				$email_to	= $_ticket_info["email"];
				$name_to	= _prepare_html($_ticket_info["name"]);
				$subject	= "Response to support ticket #".$TICKET_ID;
				$send_result= common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
				// Store processed tickets ids
				$processed_tickets_ids[$_ticket_info["id"]] = $_ticket_info["id"];
			}
			// Mass change status on reply
			if (!empty($processed_tickets_ids)) {
				db()->query(
					"UPDATE ".db('help_tickets')." 
					SET status = '".(!empty($_POST["reply_close"]) ? "closed" : "read")."' 
					WHERE id IN(".implode(",", $processed_tickets_ids).")"
				);
			}

		// ###########################################
		}
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Format given text (convert BB Codes, new lines etc)
	*
	* @access	private
	* @return	string
	*/
	function _format_text ($body = "") {
		// Stop here if text is empty
		if (empty($body)) {
			return "";
		}
		// If special code is "on" - process it
		if ($this->USE_BB_CODES) {
			$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
		}
		// We cannot die, need to be safe
		if ($this->USE_BB_CODES && is_object($BB_CODES_OBJ)) {
			$body = $BB_CODES_OBJ->_process_text($body);
		} else {
			$body = nl2br(_prepare_html($body, 0));
		}
		return $body;

	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		if (!$this->USE_FILTER || !in_array($_GET["action"], array(
			"show",
			"clear_filter",
			"save_filter",
		))) return "";
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"admin_priority"=> 'select_box("admin_priority",$this->_priorities2,	$selected, "", 2, "", false)',
			"category_id"	=> 'select_box("category_id",	$this->_help_cats2,		$selected, "", 2, "", false)',
			"status"		=> 'select_box("status",		$this->_ticket_statuses2,$selected, "", 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
			"per_page"		=> 'select_box("per_page",		$this->_per_page,		$selected, 0, 2, "", 0)',
		));
		$this->_priorities2[" "]	= t("-- All --");
		foreach ((array)$this->_priorities as $k => $v) {
			$this->_priorities2[$k]	= $v;
		}
		$this->_help_cats2[" "]	= t("-- All --");
		foreach ((array)$this->_help_cats as $k => $v) {
			$this->_help_cats2[$k]	= $v;
		}
		$this->_ticket_statuses2[" "]	= t("-- All --");
		foreach ((array)$this->_ticket_statuses as $k => $v) {
			$this->_ticket_statuses2[$k]	= $v;
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"id",
			"user_id",
			"name",
			"email",
			"status",
			"admin_priority",
			"assigned_to",
			"opened_date",
			"closed_date",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_id",
			"subject",
			"message",
			"account_type",
			"email",
			"category_id",
			"status",
			"admin_priority",
			"assigned_to",
			"sort_by",
			"sort_order",
			"per_page",
		);
		// Number of records per page
		$this->_per_page = array(
			50	=> 50,
			100	=> 100,
			200	=> 200,
			500	=> 500,
		);
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) {
			$SF[$k] = trim($v);
		}
		// Generate filter for the common fileds
		if ($SF["category_id"])	{
		 	$sql[] = " AND category_id = ".intval($SF["category_id"])." ";
		}
		if ($SF["user_id"])	{
		 	$sql[] = " AND user_id = ".intval($SF["user_id"])." ";
		}
		if (strlen($SF["admin_priority"])) {
		 	$sql[] = " AND admin_priority = ".intval($SF["admin_priority"])." ";
		}
		if ($this->DEF_VIEW_STATUS || $SF["status"]) {
			$status = $SF["status"] ? $SF["status"] : $this->DEF_VIEW_STATUS;
			if ($status == "not_closed") {
			 	$sql[] = " AND status != 'closed' ";
			} else {
			 	$sql[] = " AND status = '"._es($SF["status"])."' ";
			}
		}
		if (strlen($SF["subject"])) {
			$sql[] = " AND subject LIKE '"._es($SF["subject"])."%' ";
		}
		if (strlen($SF["message"])) {
			$sql[] = " AND message LIKE '"._es($SF["message"])."%' ";
		}
		if (!empty($SF["email"])) {
			$sql[] = " AND email LIKE '"._es($SF["email"])."%' ";
		}
		if ($SF["assigned_to"])	{
		 	$sql[] = " AND assigned_to = ".intval($SF["assigned_to"])." ";
		}
		// Add subquery to users table
		if (!empty($users_sql)) {
			$sql[] = " AND user_id IN( SELECT id FROM ".db('user')." WHERE 1=1 ".$users_sql.") ";
		}
		// Default sorting
		if (!$SF["sort_by"]) {
			$SF["sort_by"]		= "opened_date";
			$SF["sort_order"]	= "DESC";
		}
		// Sorting here
		if ($SF["sort_by"]) {
		 	$sql[] = " ORDER BY ".($this->_sort_by[$SF["sort_by"]] ? $this->_sort_by[$SF["sort_by"]] : $SF["sort_by"])." ";
			if (strlen($SF["sort_order"])) {
				$sql[] = " ".$SF["sort_order"]." ";
			}
		}
		$sql = implode("\r\n", (array)$sql);
		return $sql;
	}

	//-----------------------------------------------------------------------------
	// Session - based filter
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$selected = $_SESSION[$this->_filter_name][$item_name];
			if ($item_name == "status" && !$selected && $this->DEF_VIEW_STATUS) {
				$selected = $this->DEF_VIEW_STATUS;
			}
			$replace[$item_name."_box"] = $this->_box($item_name, $selected);
		}
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	//-----------------------------------------------------------------------------
	// Filter save method
	function save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_REQUEST["country"]) && substr($_REQUEST["country"], 0, 2) == "f_") {
			$_REQUEST["country"] = substr($_REQUEST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) {
				$_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
			}
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
