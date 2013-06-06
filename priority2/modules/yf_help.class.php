<?php

//-----------------------------------------------------------------------------
// Help section module
class yf_help extends yf_module {

	/** @var string */
	var $TICKET_DELIM	= "jq";
	/** @var array @conf_skip */
	var $_comments_params = array(
		"return_action"		=> "view_answers",
		"stpl_main"			=> "help/answers_main",
		"stpl_item"			=> "help/answers_item",
		"stpl_add"			=> "help/answers_add_form",
		"stpl_edit"			=> "help/answers_edit_form",
		"allow_guests_posts"=> 1,
	);
	/** @var bool */
	var $ALLOW_CLOSE_OWN_TICKETS	= 1;
	/** @var bool */
	var $ALLOW_DELETE_OWN_TICKETS	= 0;
	/** @var bool */
	var $ALLOW_REOPEN_OWN_TICKETS	= 1;
	/** @var bool */
	var $ALLOW_EDIT_OWN_ANSWERS		= 0;
	/** @var bool */
	var $ALLOW_DELETE_OWN_ANSWERS	= 0;
	/** @var bool */
	var $USE_CAPTCHA = 1;

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Array of select boxes to process
		$this->_boxes = array(
			"priority"	=> 'select_box("priority",	$this->_priorities,		$selected, "", 2, "", false)',
			"cat_id"	=> 'select_box("cat_id",	$this->_help_cats,		$selected, "", 2, "", false)',
			"status"	=> 'select_box("status",	$this->_ticket_statuses,$selected, "", 2, "", false)',
		);
		// Priorities array
		$this->_priorities = array(
			4	=> t("Urgent"),
			3	=> t("High"),
			2	=> t("Medium"),
			1	=> t("Low"),
		);
		// Prepare categories
		$this->CATS_OBJ		= main()->init_class("cats", "classes/");
		$this->_help_cats	= $this->CATS_OBJ->_prepare_for_box("", 0);
		$this->_help_cats	= my_array_merge(array("" => ""), (array)$this->_help_cats);
		// Array of statuses
		$this->_ticket_statuses = array(
			"new"		=> t("new"),
			"read"		=> t("read"),
			"open"		=> t("open"),
			"closed"	=> t("closed"),
		);
		// Disallow to break words in comments
		$GLOBALS["PROJECT_CONF"] = my_array_merge((array)$GLOBALS["PROJECT_CONF"], array(
			"comments"	=> array(
				"AUTO_FILTER_INPUT_TEXT"	=> 0,
			),
		));
		if ($this->USE_CAPTCHA) {
			// Try to init captcha
			$this->CAPTCHA = main()->init_class("captcha", "classes/");
//			$this->CAPTCHA->set_image_size(120, 50);
//			$this->CAPTCHA->font_height = 16;
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		$replace = array(
			"add_link_url"	=> $this->USER_ID ? "./?object=links&action=account"._add_get() : "./?object=links&action=login",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Form to contact with site admin
	function email_form ($A = array()) {
		if (!count($A) && $this->USER_ID) {
			$A2 = user($this->USER_ID, array("name","email","nick"));
		}
		$A = array_merge((array)$A2, (array)$A);
		// Process template
		$replace = array(
			"form_action"	=> process_url("./?object=help&action=send_email"),
			"name"			=> _display_name($A),
			"email"			=> $A["email"],
			"subject"		=> $A["subject"],
			"message"		=> $A["message"],
			"urls"			=> $A["urls"],
			"priority_box"	=> $this->_box("priority", $A["priority"] ? $A["priority"] : 2),
			"category_box"	=> $this->_box("cat_id", $A["cat_id"] ? $A["cat_id"] : 1),
			"error_message"	=> _e(),
			"captcha_block"	=> is_object($this->CAPTCHA) ? $this->CAPTCHA->show_block("./?object=".$_GET["object"]."&action=captcha_image") : "",
		);
		return tpl()->parse($_GET["object"]."/email_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Sending email function
	function send_email () {
		if (count($_POST) <= 1) {
			return js_redirect("./?object=".$_GET["object"]."&action=email_form");
		}
		$RUN_TIME = time();
		foreach ((array)$_POST as $k => $v) trim($_POST[$k]);
		// Check required fields
		if (!strlen($_POST["name"])) {
			common()->_raise_error(t("Please enter your name."));
		}
		if (!strlen($_POST["email"])) {
			common()->_raise_error(t("Please enter contact email."));
		} elseif (!common()->email_verify($_POST["email"])) {
			common()->_raise_error(t("That email address does not appear to be valid.<br>Please correct it and try again"));
		}
		if (!strlen($_POST["message"])) {
			common()->_raise_error(t("Please enter message."));
		}
		if (!strlen($_POST["subject"])) {
			common()->_raise_error(t("Please enter subject."));
		}
		if (empty($_POST["cat_id"]) || !isset($this->_help_cats[$_POST["cat_id"]])) {
			common()->_raise_error(t("Please select request category."));
		}
		if (empty($_POST["priority"]) || !isset($this->_priorities[$_POST["priority"]])) {
			common()->_raise_error(t("Please select priority."));
		}
		// Validate captcha
		if (is_object($this->CAPTCHA)) {
			$this->CAPTCHA->check("captcha");
		}
		// Save ticket
		if (!common()->_error_exists()) {
			// Try to find user id
			if ($this->USER_ID) {
				$user_id = $this->USER_ID;
			} else {
				// Check by email
				$A = db()->query_fetch("SELECT `id` FROM `".db('user')."` WHERE `email`='"._es($_POST["email"])."' LIMIT 1");
				$user_id = $A["id"];
			}
			// Prepare ticket id
			$TICKET_ID = md5(microtime(true). $user_id. $_POST["email"]. $_POST["message"]);
			// Do add record
			db()->INSERT("help_tickets", array(
				"ticket_key"		=> _es($TICKET_ID),
				"user_id"			=> intval($user_id),
				"name"				=> _es($_POST["name"]),
				"email"				=> _es($_POST["email"]),
				"subject"			=> _es($_POST["subject"]),
				"user_priority"		=> intval($_POST["priority"]),
				"admin_priority"	=> intval($_POST["priority"]),
				"category_id"		=> intval($_POST["cat_id"]),
				"message"			=> _es($_POST["message"]),
				"urls"				=> _es($_POST["urls"]),
				"opened_date"		=> intval($RUN_TIME),
				"closed_date"		=> 0,
				"admin_comments"	=> "",
				"status"			=> "new",
				"user_agent"		=> _prepare_html($_SERVER["HTTP_USER_AGENT"]),
				"ip"				=> _prepare_html(common()->get_ip()),
				"cookies_enabled"	=> conf('COOKIES_ENABLED') ? 1 : 0,
				"site_id"			=> (int)conf('SITE_ID'),
				"referer"			=> _es($_SERVER["HTTP_REFERER"]),
			));
			// Resirect user to the success message
			js_redirect("./?object=".$_GET["object"]."&action=email_sent&id=".$TICKET_ID);
			// Send emails in background
			ignore_user_abort(1);
			// Try to send mail to admin
			if (!common()->_error_exists()) {
				$text		= $_POST["message"];
				$email_from	= $_POST["email"];
				$name_from	= $_POST["name"];
				$email_to	= SITE_ADMIN_HELP_EMAIL;
				$name_to	= SITE_ADVERT_NAME." Admin";
				$subject	= "Help: ".$_POST["priority"].":".$_POST["category"].$_POST["subject"];
				$result		= common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
				// Check if email is sent - else show error
				if (!$result) {
					common()->_raise_error(t("Server mail error. Please try again"));
				}
			}
			// Try to send mail to user
			if (!common()->_error_exists()) {
				$replace = array(
					"request_subject"	=> $_POST["subject"],
					"request_message"	=> $_POST["message"],
					"view_answers_link"	=> process_url("./?object=help&action=view_answers&id=".$TICKET_ID),
				);
				$text		= tpl()->parse($_GET["object"]."/email_to_user", $replace);
				$email_from	= SITE_ADMIN_EMAIL;
				$name_from	= SITE_ADVERT_NAME." Admin";
				$email_to	= $_POST["email"];
				$name_to	= _prepare_html($_POST["name"]);
				$subject	= "Help: ".$_POST["priority"].":".$_POST["category"].$_POST["subject"];
				$result		= common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
				// Check if email is sent - else show error
				if (!$result) {
					common()->_raise_error(t("Server mail error. Please try again"));
				}
			}
		} else {
			$body .= $this->email_form($_POST);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Display success message
	function email_sent () {
		$replace = array(
			"view_answers_link"	=> "./?object=".$_GET["object"]."&action=view_answers&id=".$_GET["id"],
		);
		return tpl()->parse($_GET["object"]."/email_sent", $replace);
	}

	//-----------------------------------------------------------------------------
	// Display requests sent by user
	function view_tickets () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Connect pager
		$sql = "SELECT * FROM `".db('help_tickets')."` WHERE `user_id`=".intval($this->USER_ID);
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get tickets from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$CUR_TICKET_ID = $A["ticket_key"];
			$ALLOW_DELETE	= $this->ALLOW_DELETE_OWN_TICKETS;
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"ticket_id"		=> _prepare_html($CUR_TICKET_ID),
				"subject"		=> _prepare_html($A["subject"]),
				"priority"		=> $this->_priorities[$A["admin_priority"]],
				"cat_name"		=> $this->_help_cats[$A["category_id"]],
				"opened_date"	=> _format_date($A["opened_date"], "long"),
				"closed_date"	=> !empty($A["closed_date"]) ? _format_date($A["closed_date"], "long") : "",
				"status"		=> _prepare_html($A["status"]),
				"view_link"		=> "./?object=".$_GET["object"]."&action=view_answers&id=".$CUR_TICKET_ID,
				"delete_link"	=> $ALLOW_DELETE ? "./?object=".$_GET["object"]."&action=delete_ticket&id=".$CUR_TICKET_ID : "",
			);
			$items .= tpl()->parse($_GET["object"]."/view_tickets_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/view_tickets_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Close given ticket
	function close_ticket () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Try to decode ticket id
		$TICKET_ID		= $_GET["id"];
		// Try to get ticket info
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->query_fetch("SELECT * FROM `".db('help_tickets')."` WHERE `ticket_key`='"._es($TICKET_ID)."'");
		}
		if (empty($ticket_info)) {
			return _e("No such ticket!");
		}
		// Check permissions
		if (!$this->ALLOW_CLOSE_OWN_TICKETS) {
			return _e("You are not allowed to close tickets!");
		}
		// Check permissions
		if ($ticket_info["status"] == "closed") {
			return _e("This ticket is already closed!");
		}
		// Save data
		if (isset($_POST["go"])) {
			db()->UPDATE("help_tickets", array(
				"status"		=> "closed",
				"closed_date"	=> time(),
			), "`id`=".intval($ticket_info["id"]));
		}
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=view_answers&id=".$_GET["id"]);
	}

	//-----------------------------------------------------------------------------
	// Re-open given ticket
	function reopen_ticket () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Try to decode ticket id
		$TICKET_ID		= $_GET["id"];
		// Get ticket info
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->query_fetch("SELECT * FROM `".db('help_tickets')."` WHERE `ticket_key`='"._es($TICKET_ID)."'");
		}
		if (empty($ticket_info)) {
			return _e("No such ticket!");
		}
		// Check permissions
		if (!$this->ALLOW_REOPEN_OWN_TICKETS) {
			return _e("You are not allowed to re-open tickets!");
		}
		// Save data
		if (isset($_POST["go"]) && $ticket_info["status"] == "closed") {
			db()->UPDATE("help_tickets", array(
				"status"		=> "open",
				"closed_date"	=> 0,
			), "`id`=".intval($ticket_info["id"]));
		}
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=view_answers&id=".$_GET["id"]);
	}

	/**
	* Add new comment
	*
	* @access	private
	* @return	void
	*/
	function add_comment () {
		// Try to decode ticket id
		$TICKET_ID		= $_GET["id"];
		// Get ticket info
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->query_fetch("SELECT * FROM `".db('help_tickets')."` WHERE `ticket_key`='"._es($TICKET_ID)."'");
		}
		if (empty($ticket_info)) {
			return _e("No such ticket!");
		}
		if ($ticket_info["status"] == "closed") {
			db()->UPDATE("help_tickets", array(
				"status"		=> "open",
				"closed_date"	=> 0,
			), "`id`=".intval($ticket_info["id"]));
		}
		if (!$this->USER_ID && !$_POST["user_name"]) {
			$_POST["user_name"] = $ticket_info["name"];
		}
		$this->_comments_params["object_id"] = $ticket_info["id"];
		// Do add answer
		$COMMENTS_OBJ = main()->init_class("comments", USER_MODULES_DIR);
		ob_start(); // To prevent wrong redirect
		$COMMENTS_OBJ->_add($this->_comments_params);
		ob_end_clean();
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=view_answers&id=".$ticket_info["ticket_key"]);
	}

	//-----------------------------------------------------------------------------
	// Delete given ticket
	function delete_ticket () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Try to decode ticket id
		$TICKET_ID		= $_GET["id"];
		// Get ticket info
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->query_fetch("SELECT * FROM `".db('help_tickets')."` WHERE `ticket_key`='"._es($TICKET_ID)."'");
		}
		if (empty($ticket_info)) {
			return _e("No such ticket!");
		}
		// Check permissions
		if (!$this->ALLOW_DELETE_OWN_TICKETS) {
			return _e("You are not allowed to delete own tickets!");
		}
		// Remove activity points
		common()->_remove_activity_points($this->USER_ID, "bug_report", $ticket_info["id"]);
		// Do delete records
		db()->query("DELETE FROM `".db('help_tickets')."` WHERE `id`=".intval($ticket_info["id"]));
		db()->query("DELETE FROM `".db('comments')."` WHERE `object_name`='".$_GET["object"]."' AND `object_id`=".intval($ticket_info["id"]));
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=view_tickets");
	}

	//-----------------------------------------------------------------------------
	// Display ansers for the given ticket
	function view_answers () {
		// Try to decode ticket id
		$TICKET_ID		= $_GET["id"];
		// Get ticket info
		if (!empty($TICKET_ID)) {
			$ticket_info = db()->query_fetch("SELECT * FROM `".db('help_tickets')."` WHERE `ticket_key`='"._es($TICKET_ID)."'");
		}
		if (empty($ticket_info)) {
			return _e("No such ticket!");
		}
		$this->_cur_ticket_info = $ticket_info;
		// Check if ticket is closed
		$this->_ticket_is_closed = $ticket_info["status"] == "closed" || !empty($ticket_info["closed_date"]);
		// Get total number of answers
		$num_comments = $this->_get_num_comments($ticket_info["id"]);
		$total = $num_comments[$ticket_info["id"]];
		// Prepare additional comments params
		$this->_comments_params["object_id"] = $ticket_info["id"];
		$this->_comments_params["add_form_action"] = "./?object=".$_GET["object"]."&action=add_comment&id=".$TICKET_ID;
		// Process template
		$replace = array(
			"form_action"			=> !$ticket_is_closed ? "./?object=".$_GET["object"]."&action=do_answer&id=".$TICKET_ID : "",
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> intval($total),
			"ticket_id"				=> $TICKET_ID,
			"ticket_subject"		=> _prepare_html($ticket_info["subject"]),
			"ticket_message"		=> nl2br(_prepare_html($ticket_info["message"])),
			"ticket_opened_date"	=> _format_date($ticket_info["opened_date"], "long"),
			"ticket_closed_date"	=> !empty($ticket_info["closed_date"]) ? _format_date($ticket_info["closed_date"], "long") : "",
			"ticket_priority"		=> _prepare_html($this->_priorities[$ticket_info["admin_priority"]]),
			"ticket_category"		=> _prepare_html($this->_help_cats[$ticket_info["category_id"]]),
			"ticket_urls"			=> nl2br(_prepare_html($ticket_info["urls"])),
			"ticket_status"			=> _prepare_html($ticket_info["status"]),
			"ticket_is_closed"		=> intval($this->_ticket_is_closed),
			"close_link"			=> $this->USER_ID && $this->ALLOW_CLOSE_OWN_TICKETS && !$this->_ticket_is_closed ? "./?object=".$_GET["object"]."&action=close_ticket&id=".$TICKET_ID : "",
			"reopen_link"			=> $this->USER_ID && $this->ALLOW_REOPEN_OWN_TICKETS && $this->_ticket_is_closed ? "./?object=".$_GET["object"]."&action=reopen_ticket&id=".$TICKET_ID : "",
			"answers"				=> $this->_view_comments(),
		);
		return tpl()->parse($_GET["object"]."/view_answers_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Display help toolip (for AJAX style calls)
	function show_tip () {
		main()->NO_GRAPHICS = true;
		// Try to get tip id
		$TIP_ID = substr($_REQUEST["id"], strlen("help_"));
		if (!empty($TIP_ID)) {
			$tips_array = main()->get_data("locale:tips");
			$tip_info = $tips_array[$TIP_ID];
		}
		$body = "";
		if (empty($tip_info)) {
			if ($_GET["page"] != "no_debug") {
				$body = "No info";
			}
		} else {
			$body = stripslashes($tip_info["text"]);
			if ($_GET["page"] != "no_debug") {
				$body = nl2br($body);
			}
		}
		if (DEBUG_MODE && $_GET["page"] != "no_debug") {
			$body .= "<hr class='clearfloat'>DEBUG INFO:\r\n";
			$body .= common()->_show_execution_time();
		}
		echo $body;
	}

	/**
	* Check if post comment is allowed (abstract)
	*
	* @access	private
	* @return	void
	*/
	function _comment_is_allowed ($params = array()) {
		// Check for tickets opened by guests for guests
		if (empty($this->USER_ID)) {
			if (!empty($this->_cur_ticket_info["user_id"]) || $this->_ticket_is_closed) {
				return false;
			}
		}
		return true;
	}

	//-----------------------------------------------------------------------------
	// Check if comment edit allowed
	function _comment_edit_allowed ($params = array()) {
		$edit_allowed	= $this->USER_ID && $this->ALLOW_EDIT_OWN_ANSWERS && $params["user_id"] && $params["user_id"] == $this->USER_ID;
		return (bool)$edit_allowed;
	}

	//-----------------------------------------------------------------------------
	// Check if comment delete allowed
	function _comment_delete_allowed ($params = array()) {
		$delete_allowed	= $this->USER_ID && $this->ALLOW_DELETE_OWN_ANSWERS && $params["user_id"] && $params["user_id"] == $this->USER_ID;
		return (bool)$delete_allowed;
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
