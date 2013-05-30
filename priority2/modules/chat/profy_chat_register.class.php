<?php

/**
* Chat Register
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_chat_register {

	var $CHAT_OBJ = null;

	/**
	* Chat Register
	*/
	function profy_chat_register () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}
	
	/**
	* Show registration form
	*/
	function _form() {
		// Array of required fields
		$required_fields = array(
			"login",
			"password",
			"email",
		);
		foreach ((array)$required_fields as $v) {
			$v3.= "'".t($v)."', ";
			$v2.= "'".$v."', ";
		}
		$replace = array(
			"css_src"		=> $this->CHAT_OBJ->CSS_SRC,
			"charset"		=> conf('charset'),
			"form_action"	=> process_url("./?object=".CHAT_CLASS_NAME."&action=do_register"),
			"commands_url"	=> process_url("./?object=".CHAT_CLASS_NAME."&action=show_commands"),
			"tasks_1"		=> substr($v2, 0, -2),
			"tasks_2"		=> substr($v3, 0, -2),
			"login"			=> "",
			"email"			=> "",
		);
		return tpl()->parse("chat/register_form", $replace);
	}
	
	/**
	* Make user registration
	*/
	function _do() {
		// Check allowed values
		if (strlen($_POST["login"]) < 2 || strlen($_POST["login"]) > 32) {
			common()->_raise_error(t("2 > login > 32"));
		}
		$LOGIN_EXISTS = db()->query_num_rows("SELECT `id` FROM `".db('chat_users')."` WHERE `login`='"._es($_POST["login"])."'");
		if ($LOGIN_EXISTS) {
			common()->_raise_error(t('login_exists'));
		}
		if (strlen($_POST["password"]) < 4 || strlen($_POST["password"]) > 32) {
			common()->_raise_error(t("4 > password > 32"));
		}
		if (!preg_match("/^[\w\s\x7F-\xFF\._\-+=:;~!@#%\^&\*\(\)\{\}\[\]]{2,32}$/i", $_POST["login"])) {
			common()->_raise_error(t("wrong_login")." \"".$_POST["login"]."\"");
		}
		if (!common()->_error_exists()) {
			$sql = "INSERT INTO `".db('chat_users')."` (
					`login`,
					`password`,
					`gender`,
					`email`,
					`add_date`
				) VALUES (
					'"._es($_POST["login"])."',
					'"._es($_POST["password"])."',
					'".($_POST["gender"] == "m" ? "m" : "f")."',
					'"._es($_POST["email"])."',
					".time()."
				)\r\n";
			db()->query($sql);
			// Send email notification to registered user
			$replace = array(
				"login"		=> $_POST["login"],
				"password"	=> $_POST["password"],
			);
			$text = $html = tpl()->parse("chat/register_mail", $replace);
			common()->send_mail("admin@chat.profy.net", "profy_chat_admin", $_POST["email"], $_POST["login"], t("chat_registration"), $text, $html);
			$body .= "<script>alert('".t("registratiom_successful")."');</script>\r\n";
			js_redirect("./?object=".CHAT_CLASS_NAME);
		} else $body .= common()->_show_error_message();
		return $body;
	}

	/**
	* Auto create user's profile (if we are in "global" mode)
	* 
	* @access	private
	* @return	mixed	array if success, false otherwise
	*/

// TODO

/*
	function _auto_create_user_profile () {
		// Check mode
		if (!$this->SETTINGS["USE_GLOBAL_USERS"] || !main()->USER_ID) {
			return false;
		}
		// Check if such user already exists
		$user_info = db()->query_fetch("SELECT `id` FROM `".db('forum_users')."` WHERE `id`=".intval(main()->USER_ID));
		if (!empty($user_info["id"])) {
			return false;
		}
		// Do create profile
		db()->INSERT("forum_users", array(
			"id"			=> intval(main()->USER_ID),
//			"user_email"	=> _es($user_info["email"]),
//			"name"			=> _es($user_info["login"]),
			"user_timezone"	=> floatval(0),
			"dst_status"	=> intval((bool) $user_info["dst_status"]),
			"user_regdate"	=> time(),
		));
		// Return result array
		return db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval(main()->USER_ID)." LIMIT 1");
	}
*/
}
