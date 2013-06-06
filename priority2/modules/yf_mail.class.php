<?php

/**
* Internal mail manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_mail {

	/** @var array Array of mail statuses */
	var $_mail_statuses = array(
		"read",
		"unread",
		"replied",
		"sent",
		"approved",
		"disapproved",
	);
	/** @var array Array of mail types */
	var $_mail_types = array(
		"standard",
	);
	/** @var int Max lengths of subject (in bytes) */
	var $MAX_LENGTH_SUBJECT = 255;
	/** @var int Max lengths of message (in bytes) */
	var $MAX_LENGTH_MSG		= 5000;
	/** @var Number of message to display on one page (If is "null" - default system value will be used) */
	var $MESSAGES_ON_PAGE	= null;

	/**
	* Constructor (PHP 4.x)
	*
	* @access	public
	* @return	void
	*/
	function yf_mail () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @access	public
	* @return	void
	*/
	function __construct () {
		// Current user ID
		$this->USER_ID		= $_SESSION['user_id'];
		$this->USER_GROUP	= $_SESSION["user_group"];
		// Select user details
		if ($this->USER_ID) {
			$this->_user_info = &main()->USER_INFO;
			if (!$this->_user_info) {
				$this->_user_info = user($this->USER_ID);
			}
		}
		$this->_mail_folders = array();
		// Stop here for guests
		if (empty($this->USER_ID)) return false;
		// Get mail folders
		$this->_mail_folders = main()->get_data("mail_folders");
		// Cleanup mail folders for the current user
		foreach ((array)$this->_mail_folders as $folder_id => $v) {
			if (!empty($v["user_groups"])) {
				$tmp_array = explode(",", $v["user_groups"]);
				if (!in_array($this->USER_GROUP, $tmp_array)) unset($this->_mail_folders[$folder_id]);
			}
		}
		// Init friends module
		$this->FRIENDS_OBJ = main()->init_class("friends");
	}

	/**
	* Default method (currently alias for the inbox)
	*
	* @access	public
	* @return	string
	*/
	function show () {
		if (!empty($_GET["folder"]) && in_array($_GET["folder"], $this->_mail_folders)) {
			return $this->_view_folder($_GET["folder"]);
		}
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* View folder contents
	*
	* @access	public
	* @return	string
	*/
	function _view_folder ($folder_name = "", $folder_title = "", $items_stpl_name = "view_folder_item") {
		// Swap page number and id
		if (isset($_GET["id"])) {
			$_GET["page"] = intval($_GET["id"]);
			unset($_GET["id"]);
		}
		if (empty($this->USER_ID)) {
			common()->_raise_error(t("Only for members!"));
			return common()->_show_error_message();
		}
		// Get folder id
		$folder_id = $this->_get_folder_id($folder_name);
		if (empty($folder_id)) {
			common()->_raise_error(t("Missing folder id!"));
			return common()->_show_error_message();
		}
		// Divide pages
		$sql = "SELECT * FROM `".db('mail')."` WHERE `active`='1' AND `folder_id`=".intval($folder_id)." AND `receiver_id`=".intval($this->USER_ID)." ORDER BY `add_date` DESC";
		$path = "./?object=".$_GET["object"]."&action=".$_GET["action"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->MESSAGES_ON_PAGE);
		// Get folder contents from db
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) $messages_array[$A["id"]] = $A;
		// Get users names
		if (is_array($messages_array)) {
			foreach ((array)$messages_array as $msg_info) {
				$user_ids[$msg_info["sender_id"]]	= $msg_info["sender_id"];
				$user_ids[$msg_info["receiver_id"]]	= $msg_info["receiver_id"];
			}
/*
			$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `active`='1' AND `id` IN(".implode(",", $user_ids).")");
			while ($A = db()->fetch_assoc($Q)) $user_names[$A["id"]] = $A["name"];
*/
			foreach (user($users_ids, "full", array("WHERE" => array("active" => 1))) as $A) {
				$user_names[$A["id"]] = $A["name"];
			}
			
		}
		// Process messages
		foreach ((array)$messages_array as $msg_id => $msg_info) {
			$target_user_id = $msg_info["sender_id"] != $this->USER_ID ? $msg_info["sender_id"] : $msg_info["receiver_id"];
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"msg_id"			=> intval($msg_id),
				"add_date"			=> _format_date($msg_info["add_date"], "short"),
				"user_avatar"		=> _show_avatar ($target_user_id, $user_names[$target_user_id], 1, 0),
				"target_user_id"	=> $target_user_id,
				"target_user_name"	=> _prepare_html($user_names[$target_user_id]),
				"target_user_link"	=> _profile_link($target_user_id),
				"mail_status"		=> $msg_info["status"],
				"subject"			=> _prepare_html($msg_info["subject"]),
				"view_full_link"	=> $msg_info["type"] == "standard" ? "./?object=".$_GET["object"]."&action=view_full_msg&id=".$msg_id : "",
				"approve_link"		=> $msg_info["type"] != "standard" ? "./?object=".$_GET["object"]."&action=approve_msg&id=".$msg_id : "",
				"unapprove_link"	=> $msg_info["type"] != "standard" ? "./?object=".$_GET["object"]."&action=unapprove_msg&id=".$msg_id : "",
				"delete_link"		=> "./?object=".$_GET["object"]."&action=delete_msg&id=".$msg_id,
				"send_msg_link"		=> "./?object=".$_GET["object"]."&action=send&id=".$target_user_id,
				"is_std_msg"		=> intval($msg_info["type"] == "standard"),
			);
			$items .= tpl()->parse($_GET["object"]."/".$items_stpl_name, $replace2);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=delete_msg",
			"folder_title"	=> _prepare_html($folder_title),
			"is_std_folder"	=> !empty($items) ? intval($folder_info["is_standard"]) : "",
			"items"			=> $items,
			"total"			=> intval($total),
			"pages"			=> trim($pages),
		);
		return tpl()->parse($_GET["object"]."/view_folder_main", $replace);
	}

	/**
	* View inbox
	*
	* @access	public
	* @return	string
	*/
	function view_inbox () {
		return $this->_view_folder("inbox", "Mail Inbox");
	}

	/**
	* View sent
	*
	* @access	public
	* @return	string
	*/
	function view_sent () {
		return $this->_view_folder("sent", "Sent Mail");
	}

	/**
	* View trash
	*
	* @access	public
	* @return	string
	*/
	function view_trash () {
		return $this->_view_folder("trash", "Mail Trash");
	}

	/**
	* View full message contents
	*
	* @access	public
	* @return	string
	*/
	function view_full_msg () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($this->USER_ID)) {
			common()->_raise_error(t("Only for members!"));
			return common()->_show_error_message();
		}
		// Try to get message info
		if (!empty($_GET["id"])) {
			$msg_info = db()->query_fetch("SELECT * FROM `".db('mail')."` WHERE `id`=".intval($_GET["id"])." AND `active`='1' AND `receiver_id`=".intval($this->USER_ID));
		}
		if (empty($msg_info["id"])) {
			common()->_raise_error(t("No such message!"));
			return common()->_show_error_message();
		}
		// Get target user info
//		$target_user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($msg_info["sender_id"]));
		$target_user_info = user($msg_info["sender_id"]);
		// Update message status (change to "read")
		if ($msg_info["status"] == "unread") {
			db()->query("UPDATE `".db('mail')."` SET `status`='read' WHERE `id`=".$msg_info["id"]);
		}
		// Process template
		$folder_name = $this->_mail_folders[$msg_info["folder_id"]]["name"];
		$replace = array(
			"add_date"			=> _format_date($msg_info["add_date"], "long"),
			"subject"			=> _prepare_html($msg_info["subject"]),
			"message"			=> nl2br(_prepare_html($msg_info["message"])),
			"status"			=> $msg_info["status"],
			"target_user_name"	=> $target_user_info["name"],
			"target_user_link"	=> _profile_link($target_user_info["id"]),
			"user_avatar"		=> _show_avatar ($target_user_info["id"], $target_user_info["name"], 1, 1),
			"folder_name"		=> $folder_name,
			"folder_link"		=> "./?object=".$_GET["object"]."&action=view_".$folder_name,
			"reply_link"		=> $msg_info["folder_id"] == $this->_get_folder_id("inbox") && $msg_info["receiver_id"] == $this->USER_ID ? "./?object=".$_GET["object"]."&action=reply&id=".$msg_info["id"] : "",
			"back_link"			=> $_SERVER["HTTP_REFERER"],
		);
		return tpl()->parse($_GET["object"]."/view_full_msg", $replace);
	}

	/**
	* Reply to the selected mail method
	*
	* @access	public
	* @return	string
	*/
	function reply () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($this->USER_ID)) {
			common()->_raise_error(t("Only for members!"));
			return common()->_show_error_message();
		}
		// Try to get message info
		if (!empty($_GET["id"])) {
			$msg_info = db()->query_fetch("SELECT * FROM `".db('mail')."` WHERE `id`=".intval($_GET["id"])." AND `folder_id`=".intval($this->_get_folder_id("inbox"))." AND `active`='1' AND `receiver_id`=".intval($this->USER_ID));
		}
		if (empty($msg_info["id"])) {
			common()->_raise_error(t("No such message!"));
			return common()->_show_error_message();
		}
		// Get target user info
//		$target_user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($msg_info["sender_id"]));
		$target_user_info = user($msg_info["sender_id"]);
		// Update message status (change to "read")
		if (empty($target_user_info["id"])) {
			common()->_raise_error(t("No such user!"));
			return common()->_show_error_message();
		}
		// Prepare message info for reply
		if (!isset($_POST["go"])) {
			$_POST["subject"]	= "Re: ".$msg_info["subject"];
			$_POST["msg"]		= preg_replace("/([^\r\n]+[\r\n])/ims", "> \$1", $msg_info["message"]);
		}
		$params["target_user_id"] = $target_user_info["id"];
		// Show send message dialog
		return $this->send($params);
	}

	/**
	* Send mail method
	*
	* @access	public
	* @return	string
	*/
	function send ($params = array()) {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($this->USER_ID)) {
			common()->_raise_error(t("Only for members!"));
			return common()->_show_error_message();
		}
		// Try to get target user info
		$target_user_id = !empty($params["target_user_id"]) ? $params["target_user_id"] : $_GET["id"];
		if ($target_user_id) {
//			$target_user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($target_user_id)." AND `active`='1'");
			$target_user_info = user($target_user_id, "full", array("WHERE"=>array("active"=>1)));
		}
		// Check for correct user id
		if (empty($target_user_info["id"])) {
			common()->_raise_error(t("Wrong user id!"));
			return common()->_show_error_message();
		}
		// Check if user is trying to send mail to himself
		if ($target_user_info["id"] == $this->USER_ID) {
			common()->_raise_error(t("You are trying to send mail to yourself!"));
			return common()->_show_error_message();
		}
		// Try to save mail
		if (isset($_POST["go"])) {
			// Check required data
			if (empty($_POST["subject"])) {
				common()->_raise_error(t("Subject is requred!"));
			}
			if (empty($_POST["msg"])) {
				common()->_raise_error(t("Message text is requred!"));
			}
			if (!common()->_error_exists()) {
				if (strlen($_POST["subject"]) > $this->MAX_LENGTH_SUBJECT) {
					common()->_raise_error("Subject length must be less than ".$this->MAX_LENGTH_SUBJECT." symbols!");
				}
				if (strlen($_POST["msg"]) > $this->MAX_LENGTH_MSG) {
					common()->_raise_error("Message length must be less than ".$this->MAX_LENGTH_MSG." symbols!");
				}
			}
			// Do save mail
			if (!common()->_error_exists()) {
				$mail_type = "standard";
				// Store receiver mail (folder = "inbox")
				db()->INSERT("mail", array(
					"folder_id"		=> $this->_get_folder_id("inbox"),
					"sender_id"		=> intval($this->USER_ID),
					"receiver_id"	=> intval($target_user_info["id"]),
					"subject"		=> _esf($_POST["subject"]),
					"message"		=> _esf($_POST["msg"]),
					"status"		=> 'unread',
					"type"			=> _es($mail_type),
					"add_date"		=> time(),
					"active"		=> 1,
				));
				// Store sender mail (folder = "sent")
				db()->INSERT("mail", array(
					"folder_id"		=> $this->_get_folder_id("sent"),
					"sender_id"		=> intval($target_user_info["id"]),
					"receiver_id"	=> intval($this->USER_ID),
					"subject"		=> _esf($_POST["subject"]),
					"message"		=> _esf($_POST["msg"]),
					"status"		=> 'sent',
					"type"			=> _es($mail_type),
					"add_date"		=> time(),
					"active"		=> 1,
				));
				// Add counter for sent and received emails for users
				db()->query("UPDATE `".db('user')."` SET `emailssent`=`emailssent`+1 WHERE `id`=".intval($this->USER_ID));
				db()->query("UPDATE `".db('user')."` SET `emails`=`emails`+1 WHERE `id`=".intval($target_user_info["id"]));
				// Send email notification
				$replace3 = array(
					"nick"	=> _prepare_html(_display_name($this->_user_info)),
				);
				common()->quick_send_mail($target_user_info["email"], "New Message at ".SITE_NAME, tpl()->parse("emails/after_new_msg", $replace3));
				// Return user back
				js_redirect("./?object=".$_GET["object"]."&action=view_sent");
			}
		}
		// Show form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"error_message"		=> common()->_show_error_message(),
				"user_avatar"		=> _show_avatar ($target_user_info["id"], $target_user_info["name"], 1, 1),
				"target_user_id"	=> $target_user_info["id"],
				"target_user_name"	=> _prepare_html($target_user_info["name"]),
				"target_user_link"	=> _profile_link($target_user_info["id"]),
				"subject"			=> _prepare_html($_POST["subject"]),
				"msg"				=> _prepare_html($_POST["msg"]),
			);
			$body = tpl()->parse($_GET["object"]."/send_msg_form", $replace);
		}
		return $body;
	}

	/**
	* Delete one or more messages
	*
	* @access	public
	* @return	string
	*/
	function delete_msg () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($this->USER_ID)) {
			common()->_raise_error(t("Only for members!"));
			return common()->_show_error_message();
		}
		// Try to get fans ids
		if (!empty($_GET["id"])) {
			$msg_ids = array($_GET["id"]);
		} elseif (is_array($_POST)) {
			foreach ((array)$_POST as $k => $v) {
				if (substr($k, 0, 4) != "msg_") continue;
				$cur_msg_id = intval(substr($k, 4));
				if (!empty($cur_msg_id)) $msg_ids[$cur_msg_id] = $cur_msg_id;
			}
		}
		// Check again for message ids
		if (empty($msg_ids)) {
			common()->_raise_error(t("Message id is required!"));
			return common()->_show_error_message();
		}
		// Get messages infos
		if (is_array($msg_ids)) {
			$Q = db()->query("SELECT * FROM `".db('mail')."` WHERE `id` IN(".implode(",",$msg_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				if ($A["receiver_id"] != $this->USER_ID) continue;
				$msg_infos[$A["id"]] = $A;
			}
		}
		// Process messages to delete
		foreach ((array)$msg_infos as $msg_info) {
			// Move to trash first
			if ($msg_info["folder_id"] != $this->_get_folder_id("trash")) {
				db()->query("UPDATE `".db('mail')."` SET `folder_id`=".intval($this->_get_folder_id("trash"))." WHERE `id`=".intval($msg_info["id"]));
			// Else - completely delete message
			} else {
				db()->query("DELETE FROM `".db('mail')."` WHERE `id`=".intval($msg_info["id"]));
			}
		}
		// Return user back
		js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Return folder id by given name
	*
	* @access	public
	* @return	string
	*/
	function _get_folder_id ($folder_name = "") {
		foreach ((array)$this->_mail_folders as $folder_id => $v) {
			if (strtolower($v["name"]) == strtolower($folder_name)) {
				return $folder_id;
			}
		}
		return 0; // If folder not found
	}
}
