<?php

/**
* Email page handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_email_page {

	/** @var string Variable name in session (for anti-flood) */
	var $SESSION_TTL_NAME = "sent_to_friend_time";
	/** @var int Time between two equal page sendings */
	var $TTL = 30; // In seconds
	/*
		Example of using this method:
		function email_post () {
			if (empty($_POST["go"])) {
				$text = "My cool text";
			}
			return common()->email_page($text);
		}
	*/

	/**
	* Constructor
	*/
	function yf_email_page () {
		$this->_user_info = &main()->USER_INFO;
		if (!$this->_user_info) {
			$this->_user_info = user($this->USER_ID);
		}
	}

	/**
	* Email given text to a friend
	*/
	function go ($text = "") {
		$cur_page_md5 = md5($_GET["object"]."%%".$_GET["action"]."%%".$_GET["id"]);
		// Verify and send email
		if (!empty($_POST["go"])) {
			// Check if email is already registered for someone
			if (!common()->email_verify($_POST["email"])) {
				common()->_raise_error(t("Invalid e-mail, please check your spelling!"));
			}
			if (empty($_POST["name"])) {
				common()->_raise_error(t("Friend name required!"));
			}
			if (empty($_POST["message"])) {
				common()->_raise_error(t("Message text required!"));
			}
			// Check for flood
			if (!empty($_SESSION[$this->SESSION_TTL_NAME][$cur_page_md5]) && $_SESSION[$this->SESSION_TTL_NAME][$cur_page_md5] > (time() - $this->TTL)) {
				common()->_raise_error("You are not allowed to send current page more than once in future ".($_SESSION[$this->SESSION_TTL_NAME][$cur_page_md5] + $this->TTL - time())." seconds!");
			}
			// Try to send email
			if (!common()->_error_exists()) {
				$subject		= "Your friend ".$_POST["name"]." sent to you from ".SITE_NAME;
				$text_to_send	= (!empty($_POST["comment"]) ? $_POST["comment"]."<br />\r\n<br />\r\n" : ""). $_POST["message"];
				$send_result = common()->quick_send_mail($_POST["email"], $subject, $text_to_send);
				// Anti-flooder
				$_SESSION[$this->SESSION_TTL_NAME][$cur_page_md5] = time();
				$replace2 = array(
					"result" => intval((bool) $send_result),
				);
				return tpl()->parse("system/common/email_page_result", $replace2);
			}
		}
		// Show form
		if (empty($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"error_message"	=> _e(),
				"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"name"			=> _prepare_html(isset($_POST["name"]) ? $_POST["name"] : (!empty($this->_user_info["display_name"]) ? $this->_user_info["display_name"] : $this->_user_info["name"])),
				"email"			=> _prepare_html(isset($_POST["email"]) ? $_POST["email"] : $this->_user_info["email"]),
				"message"		=> _prepare_html(isset($_POST["message"]) ? $_POST["message"] : $text),
				"comment"		=> _prepare_html($_POST["comment"]),
				"page_preview"	=> isset($_POST["message"]) ? $_POST["message"] : $text,
			);
			return tpl()->parse("system/common/email_page_form", $replace);
		}
	}
}
