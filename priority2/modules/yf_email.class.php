<?php

//-----------------------------------------------------------------------------
// Site internal mailing system
class yf_email {

	/** @var array @conf_skip Mail folders */
	public $_mail_folders = array(
		0	=> "deleted",
		1	=> "inbox",
		2	=> "sent",
		3	=> "trash",
	);
	/** @var int Limit number of daily sending emails (set to "0" to disable) */
	public $EMAILS_ALLOWED_DAILY	= 20;
	/** @var bool Really delete emails records? */
	public $DELETE_EMAIL_RECORDS	= false;

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Try to init captcha
		$this->CAPTCHA = main()->init_class("captcha", "classes/");
//		$this->CAPTCHA->set_image_size(120, 50);
//		$this->CAPTCHA->font_height = 16;
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		return $this->_view_folder("inbox");
	}

	//-----------------------------------------------------------------------------
	// Inbox folder
	function inbox () {
		return $this->_view_folder("inbox");
	}

	//-----------------------------------------------------------------------------
	// Inbox folder (alias for inbox)
	function view_inbox () {
		return $this->_view_folder("inbox");
	}

	//-----------------------------------------------------------------------------
	// Sent folder
	function sent () {
		return $this->_view_folder("sent");
	}

	//-----------------------------------------------------------------------------
	// Sent folder
	function view_sent () {
		return $this->_view_folder("sent");
	}

	//-----------------------------------------------------------------------------
	// Trash folder
	function trash () {
		return $this->_view_folder("trash");
	}

	//-----------------------------------------------------------------------------
	// Trash folder
	function view_trash () {
		return $this->_view_folder("trash");
	}

	//-----------------------------------------------------------------------------
	// View folder contents
	function _view_folder ($folder_name) {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		if (isset($_GET["id"])) {
			$_GET["page"] = intval($_GET["id"]);
			unset($_GET["id"]);
		}
		// Connect to pager
		if ($folder_name == "inbox") {
			$sql = "SELECT * FROM `".db('mailarchive')."` WHERE `receiver`=".intval($this->USER_ID)." AND `r_folder_id`=".intval($this->_get_folder_id($folder_name));
		} else { // Outbox (sent)
			$sql = "SELECT * FROM `".db('mailarchive')."` WHERE `sender`=".intval($this->USER_ID)." AND `s_folder_id`=".intval($this->_get_folder_id($folder_name));
		}
		$order_by_sql = " ORDER BY `time` DESC";
		list($add_sql, $pages, $total) = common()->divide_pages(str_replace("SELECT *", "SELECT `id`", $sql));
		// Get info from database
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($mail_info = db()->fetch_assoc($Q)) {
			if (empty($mail_info["sender"])) continue;
			$emails[$mail_info["id"]]			= $mail_info;
			$users_ids[$mail_info["sender"]]	= $mail_info["sender"];
			$users_ids[$mail_info["receiver"]]	= $mail_info["receiver"];
		}
		// Get senders users info
		if (!empty($users_ids)) {
			$users_infos = user($users_ids, array("id","name","nick","photo_verified"));
		}
		// Process user reputation
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$all_users_ids		= $users_ids;
			$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids($all_users_ids);
		}
		// Process emails
		foreach ((array)$emails as $mail_info) {
			$_user_id = $mail_info["sender"] == $this->USER_ID ? $mail_info["receiver"] : $mail_info["sender"];
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"from"			=> _display_name($users_infos[$mail_info["sender"]]),
				"to"			=> _display_name($users_infos[$mail_info["receiver"]]),
				"subject"		=> _prepare_html($mail_info["subject"]),
				"sent_date"		=> _format_date($mail_info["time"], "long"),
				"sender_ip"		=> !empty($mail_info["sender_ip"]) ? _prepare_html($mail_info["sender_ip"]) : "",
				"view_link"		=> "./?object=".$_GET["object"]."&action=view&id=".$mail_info["id"]._add_get(array("page")),
				"reply_link"	=> "./?object=".$_GET["object"]."&action=reply&id=".$mail_info["id"]._add_get(array("page")),
				"forward_link"	=> "./?object=".$_GET["object"]."&action=forward&id=".$mail_info["id"]._add_get(array("page")),
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$mail_info["id"]._add_get(array("page")),
				"user_id"		=> intval($_user_id),
				"profile_url"	=> _profile_link($_user_id),
				"user_avatar"	=> _show_avatar ($_user_id, $users_infos[$_user_id], 1),
				"reput_info"	=> is_object($REPUT_OBJ) ? $REPUT_OBJ->_show_for_user($_user_id, $users_reput_info[$_user_id], true) : "",
				"folder_name"	=> $folder_name,
				"is_unread_msg"	=> $folder_name == "inbox" && !$mail_info["r_read_time"] ? 1 : 0,
			);
			$items .= tpl()->parse(__CLASS__."/view_folder_item", $replace2);
		}
		// Link to exit escort
		if (!empty($_SESSION["edit_escort_id"]) && $_SESSION["user_group"] == 4) {
			$exit_escort_link = tpl()->parse(__CLASS__."/exit_escort", array(
				"url" => "./?object=manage_escorts&action=exit_escort",
			));
		}
		// Process template
		$replace = array(
			"folder_name"	=> t($folder_name),
			"items"			=> $items,
			"total"			=> intval($total),
			"pages"			=> $pages,
			"inbox_link"	=> "./?object=".$_GET["object"]."&action=inbox",
			"sent_link"		=> "./?object=".$_GET["object"]."&action=sent",
			
		);
		return tpl()->parse(__CLASS__."/view_folder_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// View email
	function view () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		// Get email info
		$mail_info = db()->query_fetch("SELECT * FROM `".db('mailarchive')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($mail_info["id"])) {
			return _e("No such record!");
		}
		if ($mail_info["sender"] != $this->USER_ID && $mail_info["receiver"] != $this->USER_ID) {
			return _e("Not your email!");
		}
		// Set read time for receiver (if not yet and only if inside "inbox" folder)
		if ($mail_info["receiver"] == $this->USER_ID && $mail_info["r_folder_id"] == 1 && empty($mail_info["r_read_time"])) {
			db()->UPDATE("mailarchive", array("r_read_time" => time()), "`id`=".intval($mail_info["id"]));
		}
		// Process reputation
		$reput_text = "";
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$reput_info	= $REPUT_OBJ->_get_user_reput_info($mail_info["sender"]);
			$reput_text	= $REPUT_OBJ->_show_for_user($mail_info["sender"], $reput_info, true);
		}
		// Get user info
		$sender_info = user($mail_info["sender"], array("id","name","nick","photo_verified"));
		if (empty($sender_info["id"])) {
			return _e("No such sender user in database!");
		}
		$receiver_info = user($mail_info["receiver"], array("id","name","nick","photo_verified"));
		// Process template
		$replace = array(
			"from"			=> _display_name($sender_info),
			"to"			=> _display_name($receiver_info),
			"subject"		=> _prepare_html($mail_info["subject"]),
			"message"		=> nl2br(_prepare_html($mail_info["message"])),
			"sent_date"		=> _format_date($mail_info["time"], "long"),
			"sender_ip"		=> !empty($mail_info["sender_ip"]) ? _prepare_html($mail_info["sender_ip"]) : "",
			"view_link"		=> "./?object=".$_GET["object"]."&action=view&id=".$mail_info["id"]._add_get(array("page")),
			"reply_link"	=> "./?object=".$_GET["object"]."&action=reply&id=".$mail_info["id"]._add_get(array("page")),
			"forward_link"	=> "./?object=".$_GET["object"]."&action=forward&id=".$mail_info["id"]._add_get(array("page")),
			"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$mail_info["id"]._add_get(array("page")),
			"profile_url"	=> _profile_link($mail_info["sender"]),
			"user_avatar"	=> _show_avatar ($mail_info["sender"], $sender_info, 1),
			"reput_info"	=> $reput_text,
			"folder_name"	=> $this->_mail_folders[$mail_info["sender"] == $this->USER_ID ? $mail_info["s_folder_id"] : $mail_info["r_folder_id"]],
		);
		return tpl()->parse(__CLASS__."/view_message", $replace);
	}

	//-----------------------------------------------------------------------------
	// Reply form to the selected email
	function reply () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		if ($this->_user_info["ban_email"]) {
			return _e(
				"Sorry, you are not allowed to send emails! Enjoy our site in some other way!"
				."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
			);
		}
		$_GET["id"] = intval($_GET["id"]);
		// Get mail info
		$mail_info = db()->query_fetch("SELECT * FROM `".db('mailarchive')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($mail_info["id"])) {
			return _e("No such record!");
		}
		if ($mail_info["sender"] != $this->USER_ID && $mail_info["receiver"] != $this->USER_ID) {
			return _e("Not your email!");
		}
		// Get sender info
		$receiver_info = user($mail_info["sender"]);
		if (empty($receiver_info["id"])) {
			return _e("No such sender user in database!");
		}
		// Do not allow to send to myself
		if ($receiver_info["id"] == $this->USER_ID) {
			return _e("You are trying to send email to yourself!");
		}
		// Check allowed number of sent emails per day
		if (!empty($this->EMAILS_ALLOWED_DAILY)) {
			list($num_emails_24h) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM `".db('mailarchive')."` WHERE `sender`=".intval($this->USER_ID)." AND `time` >= ".(time() - 86400));
			if ($num_emails_24h >= $this->EMAILS_ALLOWED_DAILY) {
				return _e("Email quota exceeded! To prevent our site from misuse we limit the allowed daily number of email messages to ".intval($this->EMAILS_ALLOWED_DAILY)." per user.");
			}
		}
		// Check if target user is ignored by owner
		if (common()->_is_ignored($this->USER_ID, $receiver_info["id"])) {
			return _e("You are ignored by the target user.");
		}
		// Process reputation
		$reput_text = "";
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$reput_info	= $REPUT_OBJ->_get_user_reput_info($mail_info["sender"]);
			$reput_text	= $REPUT_OBJ->_show_for_user($mail_info["sender"], $reput_info, true);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=send_mail&id=".$receiver_info["id"]._add_get(array("page")),
			"from"			=> _display_name($this->_user_info),
			"receiver_name"	=> _display_name($receiver_info),
			"receiver_id"	=> $receiver_info["id"],
			"prev_location"	=> $_SERVER["HTTP_REFERER"],
			"subject"		=> empty($_POST["subject"]) ? "RE:". stripslashes($mail_info["subject"]) : $_POST["subject"],
			"message"		=> empty($_POST["message"]) ? htmlspecialchars(str_replace("\n", "\n>>> ", stripslashes($mail_info["message"]))) : htmlspecialchars($_POST["message"]),
			"sent_date"		=> _format_date($mail_info["time"], "long"),
			"view_link"		=> "./?object=".$_GET["object"]."&action=view&id=".$mail_info["id"]._add_get(array("page")),
			"reply_link"	=> "./?object=".$_GET["object"]."&action=reply&id=".$mail_info["id"]._add_get(array("page")),
			"forward_link"	=> "./?object=".$_GET["object"]."&action=forward&id=".$mail_info["id"]._add_get(array("page")),
			"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$mail_info["id"]._add_get(array("page")),
			"captcha_block"	=> $this->CAPTCHA->show_block("./?object=".$_GET["object"]."&action=show_image"),
			"profile_url"	=> _profile_link($mail_info["sender"]),
			"user_avatar"	=> _show_avatar ($mail_info["sender"], $receiver_info, 1),
			"reput_info"	=> $reput_text,
			"is_reply"		=> 1,
		);
		return tpl()->parse(__CLASS__."/send_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Form to send email
	function send_form () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Check for email ban
		if ($this->_user_info["ban_email"]) {
			return _e(
				"Sorry, you are not allowed to send emails! Enjoy our site in some other way!"
				."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
			);
		}
		$_GET["id"] = intval($_GET["id"]);
		// Get receiver info
		$receiver_info = user($_GET["id"]);
		if (empty($receiver_info["id"])) {
			return _e("No such user in database!");
		}
		// Do not allow to send to myself
		if ($receiver_info["id"] == $this->USER_ID) {
			return _e("You are trying to send email to yourself!");
		}
		// Check allowed number of sent emails per day
		if (!empty($this->EMAILS_ALLOWED_DAILY)) {
			list($num_emails_24h) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM `".db('mailarchive')."` WHERE `sender`=".intval($this->USER_ID)." AND `time` >= ".(time() - 86400));
			if ($num_emails_24h >= $this->EMAILS_ALLOWED_DAILY) {
				return _e("Email quota exceeded! To prevent our site from misuse we limit the allowed daily number of email messages to ".intval($this->EMAILS_ALLOWED_DAILY)." per user.");
			}
		}
		// Check if target user is ignored by owner
		if (common()->_is_ignored($this->USER_ID, $receiver_info["id"])) {
			return _e("You are ignored by the target user.");
		}
		$GLOBALS['user_info'] = $receiver_info;
		// Process reputation
		$reput_text = "";
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$reput_info	= $REPUT_OBJ->_get_user_reput_info($receiver_info["id"]);
			$reput_text	= $REPUT_OBJ->_show_for_user($receiver_info["id"], $reput_info, true);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=send_mail&id=".$receiver_info["id"]._add_get(array("page")),
			"from"			=> _display_name($this->_user_info),
			"receiver_name"	=> _display_name($receiver_info),
			"receiver_id"	=> intval($receiver_info["id"]),
			"prev_location"	=> _prepare_html($_SERVER["HTTP_REFERER"]),
			"subject"		=> _prepare_html($_POST["subject"]),
			"message"		=> _prepare_html(empty($_POST["message"]) ? "Hi "._display_name($receiver_info)."," : $_POST["message"]),
			"captcha_block"	=> $this->CAPTCHA->show_block("./?object=".$_GET["object"]."&action=show_image"),
			"user_avatar"	=> _show_avatar ($receiver_info["id"], $receiver_info, 1),
			"reput_info"	=> $reput_text,
			"is_reply"		=> 0,
		);
		return tpl()->parse(__CLASS__."/send_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Send internal mail
	function send_mail () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Check user ban
		if ($this->_user_info["ban_email"]) {
			return _e(
				"Sorry, you are not allowed to send emails! Enjoy our site in some other way!"
				."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
			);
		}
		$_GET["id"] = intval($_GET["id"]);
		// Get receiver info
		$receiver_info = user($_GET["id"]);
		if (empty($receiver_info["id"])) {
			return _e("No such user!");
		}
		// Do not allow to send to myself
		if ($receiver_info["id"] == $this->USER_ID) {
			return _e("You are trying to send email to yourself!");
		}
		// Check allowed number of sent emails per day
		if (!empty($this->EMAILS_ALLOWED_DAILY)) {
			list($num_emails_24h) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM `".db('mailarchive')."` WHERE `sender`=".intval($this->USER_ID)." AND `time` >= ".(time() - 86400));
			if ($num_emails_24h >= $this->EMAILS_ALLOWED_DAILY) {
				return _e("Email quota exceeded! To prevent our site from misuse we limit the allowed daily number of email messages to ".intval($this->EMAILS_ALLOWED_DAILY)." per user.");
			}
		}
		// Check if target user is ignored by owner
		if (common()->_is_ignored($this->USER_ID, $receiver_info["id"])) {
			return _e("You are ignored by the target user.");
		}
		// Validate captcha
		$this->CAPTCHA->check("captcha");
		// Check required fields
		if (!strlen($_POST["subject"])) {
			common()->_raise_error("Subject required");
		}
		if (!strlen($_POST["message"])) {
			common()->_raise_error("Message required");
		}
		// Try to find scum words in text
		if (!common()->_error_exists()) {
			$this->_check_for_scum_words($_POST["message"]);
		}
		// Check for errors occured
		if (!common()->_error_exists()) {
			$email_from = $this->_user_info["email"];
			$name_from	= _prepare_html(_display_name($this->_user_info));
			$email_to	= $receiver_info["email"];
			$name_to	= _prepare_html(_display_name($receiver_info));
			$subject	= _prepare_html($_POST['subject']);
			// Prepare message
			$replace_email = array(
				"sender_name"	=> $name_from,
				"sender_email"	=> $email_from,
				"receiver_name"	=> $name_to,
				"receiver_email"=> $email_to,
				"subject"		=> $subject,
				"message"		=> $_POST["message"],
				"mailbox_url"	=> process_url("./?object=".$_GET["object"]."&action=inbox"._add_get(array("page"))),
			);
			$message = tpl()->parse(__CLASS__."/send_mail", $replace_email);
			// Send mail to the recipient
			$send_result = common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $message, nl2br($message));
			// Insert message into db
			db()->INSERT("mailarchive", array(
				"sender"		=> intval($this->_user_info["id"]),
				"receiver"		=> intval($receiver_info["id"]),
				"s_folder_id"	=> intval($this->_get_folder_id("sent")),
				"r_folder_id"	=> intval($this->_get_folder_id("inbox")),
				"subject"		=> _es($subject),
				"message"		=> _es($_POST["message"]),
				"time"			=> time(),
				"sender_ip"		=> _es(common()->get_ip()),
			));
			$RECORD_ID = db()->INSERT_ID();
			// Update number of emails for the users
			db()->query("UPDATE `".db('user')."` SET `emails`=`emails`+1 WHERE `id`=".$receiver_info["id"]);
			db()->query("UPDATE `".db('user')."` SET `emailssent`=`emailssent`+1 WHERE `id`=".$this->_user_info["id"]);
			// Show success message
			$replace = array(
				"receiver_name"		=> _prepare_html(_display_name($receiver_info)),
				"receiver_email"	=> _prepare_html($receiver_info["email"]),
				"sender_name"		=> _prepare_html(_display_name($this->_user_info)),
				"sender_email"		=> _prepare_html($this->_user_info["email"]),
				"subject"			=> _prepare_html($subject),
				"message"			=> _prepare_html($_POST["message"]),
				"mailbox_url"		=> process_url("./?object=".$_GET["object"]."&action=inbox"._add_get(array("page"))),
				"prev_location"		=> $_POST["prev_location"],
				"show_email"		=> intval((bool)$receiver_info["show_mail"]),
				"send_result"		=> intval((bool)$send_result),
			);
			$body = tpl()->parse(__CLASS__."/send_success", $replace);
			// Save activity log
			common()->_add_activity_points($this->_user_info["id"], "sent_mail", strlen($_POST["message"]), $RECORD_ID);
		} else {
			$body .= _e();
			$body .= $this->send_form();
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Forward message to the target user
	function forward () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		// Get mail info
		$mail_info = db()->query_fetch("SELECT * FROM `".db('mailarchive')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($mail_info["id"])) {
			return _e("No such record!");
		}
		if ($mail_info["sender"] != $this->USER_ID && $mail_info["receiver"] != $this->USER_ID) {
			return _e("Not your email!");
		}
		// Get recipient user info
		$receiver_info = user($mail_info["sender"], array("id","name","nick","email"));
		if (empty($receiver_info["id"])) {
			return _e("No such user in database!");
		}
		// Check allowed number of sent emails per day
		if (!empty($this->EMAILS_ALLOWED_DAILY)) {
			list($num_emails_24h) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM `".db('mailarchive')."` WHERE `sender`=".intval($this->USER_ID)." AND `time` >= ".(time() - 86400));
			if ($num_emails_24h >= $this->EMAILS_ALLOWED_DAILY) {
				return _e("Email quota exceeded! To prevent our site from misuse we limit the allowed daily number of email messages to ".intval($this->EMAILS_ALLOWED_DAILY)." per user.");
			}
		}
		// Send mail to the recipient
		$message = $mail_info['message'];
		$subject = $mail_info['subject'];
		common()->send_mail($this->_user_info["email"], _display_name($this->_user_info), $receiver_info["email"], _display_name($receiver_info), $subject, $message, nl2br($message));
		// Show success message
		$replace = array(
			"user_email"	=> $this->_user_info["email"],
		);
		return tpl()->parse(__CLASS__."/forward_success", $replace);
	}

	//-----------------------------------------------------------------------------
	// Delete email item
	function delete () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get current mail info (also, checking for owner)
		$mail_info = db()->query_fetch("SELECT `id`,`s_folder_id`,`r_folder_id`,`sender`,`receiver` FROM `".db('mailarchive')."` WHERE `id`=".intval($_GET["id"]));
		if ($mail_info["sender"] != $this->USER_ID && $mail_info["receiver"] != $this->USER_ID) {
			return _e("Not your email!");
		}
		if (empty($mail_info["id"])) {
			return _e("No such record!");
		}
		$need_to_delete_record = false;
		// Check if another user already deleted this email, so we need to delete record
		if ($mail_info["sender"] == $this->USER_ID) {

			if ($mail_info["r_folder_id"] == 0) {
				$need_to_delete_record = true;
			}

			db()->UPDATE("mailarchive", array(
				"s_folder_id"	=> $this->_get_folder_id("deleted"),
			),"`id`=".$mail_info["id"]);

		} elseif ($mail_info["receiver"] == $this->USER_ID) {

			if ($mail_info["s_folder_id"] == 0) {
				$need_to_delete_record = true;
			}

			db()->UPDATE("mailarchive", array(
				"r_folder_id"	=> $this->_get_folder_id("deleted"),
			),"`id`=".$mail_info["id"]);

		}
		// Do delete record if needed
		if (!$this->DELETE_EMAIL_RECORDS) {
			$need_to_delete_record = false;
		}
		if ($need_to_delete_record) {
			db()->query("DELETE FROM `".db('mailarchive')."` WHERE `id`=".$mail_info["id"]);
		}
		// Remove activity points
		if ($need_to_delete_record && $mail_info["sender"] == $this->USER_ID) {
			common()->_remove_activity_points($this->USER_ID, "sent_mail", $_GET["id"]);
		}
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=inbox"._add_get(array("page")));
	}

	//-----------------------------------------------------------------------------
	// Check current text for scum words
	function _check_for_scum_words ($message = "") {
		global $SCUM_WORDS;
		// By default think that email is correct and good
		$IS_BAD_EMAIL = false;
		if (empty($SCUM_WORDS)) return $IS_BAD_EMAIL;
		// Try to find something bad
		$found_scums = 0;
		foreach ((array)$SCUM_WORDS as $cur_scum_word)	{
			if (preg_match("#".$cur_scum_word."#i", $message)) {
				$found_scums++;
			}
		}
		if ($found_scums >= 5) {
			$IS_BAD_EMAIL = true;
		}
		if ($IS_BAD_EMAIL) {
			common()->_raise_error("Error sending mail!");
			// Do ban user if found something bad
			$NEW_ADMIN_COMMENTS = "\r\nAuto-banned on "._format_date(time())." (found scum words in email)";
			db()->query("UPDATE `".db('user')."` SET `ban_email` = '1', `admin_comments`=CONCAT(`admin_comments`, '"._es($NEW_ADMIN_COMMENTS)."') WHERE `id`=".intval($this->USER_ID));
		}
		return $IS_BAD_EMAIL;
	}

	/**
	* Return folder id by given name
	*
	* @access	private
	* @return	int
	*/
	function _get_folder_id ($folder_name = "") {
		foreach ((array)$this->_mail_folders as $folder_id => $v) {
			if (strtolower($v) == strtolower($folder_name)) {
				return $folder_id;
			}
		}
		return 0; // If folder not found
	}

	//-----------------------------------------------------------------------------
	// Show captcha image
	function show_image() {
		$this->CAPTCHA->show_image();
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$NAV_BAR_OBJ = &$params["nav_bar_obj"];
		if (!is_object($NAV_BAR_OBJ)) {
			return false;
		}
		// Save old items
		$old_items = $params["items"];
		// Create new items
		$items = array();
		if ($this->USER_ID) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("My Account", "./?object=account");
		}
		$items[]	= $NAV_BAR_OBJ->_nav_item("Your Mail", "./?object=email");
		$items[]	= $NAV_BAR_OBJ->_nav_item($NAV_BAR_OBJ->_decode_from_url($_GET["action"]));
		return $items;
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Inbox (Received)",
				"url"	=> "./?object=".$_GET["object"]."&action=inbox",
			),
			array(
				"name"	=> "Outbox (Sent)",
				"url"	=> "./?object=".$_GET["object"]."&action=sent",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}
	
	function _account_suggests(){
		// Check number of unread emails
		list($num_unread_emails) = db()->query_fetch(
			"SELECT COUNT(`id`) AS `0` FROM `".db('mailarchive')."` 
			WHERE `receiver`=".intval($this->USER_ID)." 
				AND `r_folder_id`=1 
				AND `r_read_time`=0"
		);
		
		if ($num_unread_emails) {
			$replace = array(
				"num_unread_emails"		=> $num_unread_emails,
				"inbox_link"			=> "./?object=email&action=inbox",
			);
		
			$suggests[]	= tpl()->parse(__CLASS__ ."/_account_suggests", $replace);
		}
		return $suggests;
	}
	
	/**
	*
	*/
	function _unread () {
	
		if(empty($this->_user_info["last_view"])){
			return;
		}

		// Check number of unread emails
		list($num_unread_emails) = db()->query_fetch(
			"SELECT COUNT(`id`) AS `0` FROM `".db('mailarchive')."` 
			WHERE `receiver`=".intval($this->USER_ID)." 
				AND `r_folder_id`=1 
				AND `r_read_time`=0"
		);

		$unread = array(
			"count"	=> $num_unread_emails,
			"ids"	=> "",
			"link"	=> process_url("./?object=email&action=inbox"),
		);
	
		return $unread;
	}

}

