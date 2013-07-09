<?php

//-----------------------------------------------------------------------------
// Account change email handler
class account_change_email {

	//-----------------------------------------------------------------------------
	// Constructor
	function account_change_email () {
		$this->ACCOUNT_OBJ	= module(ACCOUNT_CLASS_NAME);
	}

	//-----------------------------------------------------------------------------
	// Firt step of auto changing email
	function _first_step () {
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if (!empty($_POST["go"]) && !empty($_POST["new_email"])) {
			// Check if such email already exists in the database
			if (db()->query_num_rows("SELECT id FROM ".db('user')." WHERE email='"._es($_POST["new_email"])."'"))
				_re(tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/email_exists"));
			// Check for errors
			if (!common()->_error_exists()) {
				$time = time();
				db()->query("INSERT INTO ".db('email_change')." (user_id,old_mail,new_mail,time) VALUES (".intval($this->ACCOUNT_OBJ->USER_ID).",'"._es($this->ACCOUNT_OBJ->_user_info["email"])."','"._es($_POST["new_email"])."',".$time.")");
				$RECORD_ID = db()->insert_id();
				$code = base64_encode($RECORD_ID ."df". $this->ACCOUNT_OBJ->USER_ID. "df". $time);
				// Send email to the old address
				$replace1 = array(
					"old_email"			=> $this->ACCOUNT_OBJ->_user_info["email"],
					"new_email"			=> $_POST["new_email"],
					"email_form_url"	=> process_url("./?object=help&action=email_form"),
				);
				$text1 = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/email_to_old", $replace1);
				common()->send_mail(SITE_ADMIN_EMAIL, SITE_ADVERT_NAME, $this->ACCOUNT_OBJ->_user_info["email"], _display_name($this->ACCOUNT_OBJ->_user_info), "Your email change!", $text1, nl2br($text1));
				// Send email to the new address
				$replace2 = array(
					"confirm_code"		=> $code,
					"confirm_link"		=> process_url("./?object=".ACCOUNT_CLASS_NAME."&action=confirm_change_email&id=".$code),
					"manual_url"		=> process_url("./?object=".ACCOUNT_CLASS_NAME."&action=confirm_change_email"),
				);
				$text2 = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/email_to_new", $replace2);
				common()->send_mail(SITE_ADMIN_EMAIL, SITE_ADVERT_NAME, $_POST["new_email"], _display_name($this->ACCOUNT_OBJ->_user_info), "Email change confirmation required!", $text2, nl2br($text2));
				// Show message to user
				$body = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/send_success");
			} else $body = _e();
		} else {
			$replace = array(
				"email"			=> "",
				"form_action"	=> "./?object=".ACCOUNT_CLASS_NAME."&action=change_email",
			);
			$body = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/form", $replace);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Email change confirmation step
	function _confirm () {
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		// Try to confirm changes
		if (!empty($_REQUEST["id"])) {
			// Descrypt code
			list($record_id, $user_id, $add_time) = explode("df", base64_decode($_REQUEST["id"]));
			$A = db()->query_fetch("SELECT * FROM ".db('email_change')." WHERE id=".intval($record_id)." AND user_id=".intval($user_id)." AND time=".intval($add_time));
			// Check required data
			if (empty($record_id) || empty($user_id) || empty($add_time) || empty($A["id"]))
				_re(tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/confirm_error"));
			// Check for errors
			if (!common()->_error_exists()) {
				foreach ((array)$A as $k => $v) $A[$k] = stripslashes($v);
				db()->query("UPDATE ".db('user')." SET email='"._es($A["new_mail"])."' WHERE id=".intval($A["user_id"])." AND email='"._es($A["old_mail"])."'");
				db()->query("DELETE FROM ".db('email_change')." WHERE id=".intval($record_id));
				// Send success mails
				$replace1 = array(
					"old_email"			=> $A["old_mail"],
					"new_email"			=> $A["new_mail"],
				);
				$text1 = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/email_changed", $replace1);
				common()->send_mail(SITE_ADMIN_EMAIL, SITE_ADVERT_NAME, $A["old_mail"], _display_name($this->ACCOUNT_OBJ->_user_info), "Email changed successfully!", $text1, nl2br($text1));
				common()->send_mail(SITE_ADMIN_EMAIL, SITE_ADVERT_NAME, $A["new_mail"], _display_name($this->ACCOUNT_OBJ->_user_info), "Email changed successfully!", $text1, nl2br($text1));
				// Show success message
				$body = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/change_success");
			} else $body = _e();
		// Show manual form
		} else {
			$replace = array(
				"form_action"	=> "./?object=".ACCOUNT_CLASS_NAME."&action=confirm_change_email",
			);
			$body = tpl()->parse(ACCOUNT_CLASS_NAME."/change_email/manual_form", $replace);
		}
		return $body;
	}
}
