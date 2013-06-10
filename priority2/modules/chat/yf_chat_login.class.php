<?php

/**
* Chat Login
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_chat_login {

	public $CHAT_OBJ = null;

	/**
	* Chat Login
	*/
	function yf_chat_login () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}
	
	/**
	* Show login form
	*/
	function _form() {
		// Get chat rooms from the db
		$Q = db()->query("SELECT * FROM `".db('chat_rooms')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) $chat_rooms[$A["id"]] = t($A["name"]);
		// Get number of users in each room
		if (is_array($chat_rooms)) {
			foreach ((array)$GLOBALS['chat_online'] as $A5) {
				$online_in_rooms[$A5["room_id"]]++;
			}
			foreach ((array)$chat_rooms as $room_id => $room_name) {
				$chat_rooms[$room_id] = $room_name." (".intval($online_in_rooms[$room_id]).")";
			}
		}
		// Process template
		$replace = array(
			"css_src"			=> $this->CHAT_OBJ->CSS_SRC,
			"charset"			=> conf('charset'),
			"form_action"		=> process_url("./?object=".CHAT_CLASS_NAME."&action=do_login"),
			"commands_url"		=> process_url("./?object=".CHAT_CLASS_NAME."&action=show_commands"),
			"register_url"		=> process_url("./?object=".CHAT_CLASS_NAME."&action=register_form"),
			"rooms"				=> common()->select_box("room_id", $chat_rooms, "", false),
			"login"				=> "",
			"use_global_users"	=> intval((bool)$this->CHAT_OBJ->USE_GLOBAL_USERS),
		);
		return tpl()->parse("chat/login_form", $replace);
	}
	
	/**
	* Process login
	*/
	function _do() {
		if (!count($_POST)) {
			return js_redirect("./?object=".CHAT_CLASS_NAME."&action=login_form");
		}
		// Go
		$_POST["login"]		= trim(strip_tags($_POST["login"]));
		$_POST["password"]	= trim(strip_tags($_POST["password"]));
		$_POST["room_id"]	= trim(strip_tags($_POST["room_id"]));
		$_POST["color"]		= trim(strip_tags($_POST["color"]));
		// Check allowed values
		if (strlen($_POST["login"]) < 2 || strlen($_POST["login"]) > 32) {
			common()->_raise_error("2 > ".t("login")." > 32");
		}
		if (strlen($_POST["password"]) < 4 || strlen($_POST["password"]) > 32) {
			common()->_raise_error("4 > ".t("password")." > 32");
		}
		if (!preg_match("/^[\w\s\x7F-\xFF\._\-+=:;~!@#%\^&\*\(\)\{\}\[\]]{2,32}$/i", $_POST["login"])) {
			common()->_raise_error(t("wrong_login")." \"".$_POST["login"]."\"");
		}
		if (!(preg_match('/^#[0-9a-f]{6}$/i', $_POST["color"]) || preg_match('/^[a-z]{3,16}$/i', $_POST["color"]))) {
			common()->_raise_error(t("wrong_color"));
		}
		if (!common()->_error_exists()) {
			// Check if room exists in the database
			$A3 = db()->query_fetch("SELECT `id` FROM `".db('chat_rooms')."` WHERE `id`=".intval($_POST["room_id"])." AND `active`='1' LIMIT 1");
			if (!$A3["id"]) common()->_raise_error(t('no_such_room'));
			// Check if user exists in the database
			$A = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `login`='"._es(trim($_POST["login"]))."' AND `password`='".trim($_POST["password"])."' AND `active`='1' LIMIT 1");
			if (!$A["id"]) common()->_raise_error(t('login_failed'));
			// Check if user is already online
			foreach ((array)$GLOBALS['chat_online'] as $A5) {
				if ($A5['login'] == $_POST["login"]) {
					$A2 = $A5;
					break;
				}
			}
			if ($A2["user_id"]) {
				db()->query("DELETE FROM `".db('chat_online')."` WHERE `user_id`=".$A2["user_id"]);
				// Create log record
				$this->CHAT_OBJ->_log_change_online_status($A2, $A2["add_date"]);
			}
		}
		if (!common()->_error_exists()) {
			// Determine user info status
			$info_status = strlen($A["photo"]) ? 2 : ($A["info_add_date"] ? 1 : 0);
			// Insert data into session
			$_SESSION['chat_user_id'] = $A["id"];
			// Insert user into online table
			$sql = "INSERT INTO `".db('chat_online')."` (
					`user_id`,
					`group_id`,
					`room_id`,
					`add_date`,
					`ip`,
					`session_id`,
					`user_agent`,
					`referer`,
					`text_color`,
					`login`,
					`gender`,
					`chat_color_1`,
					`chat_color_2`,
					`chat_color_3`,
					`chat_color_4`,
					`chat_show_time`,
					`chat_refresh`,
					`chat_msg_filter`,
					`info_status`,
					`last_visit`
				) VALUES (
					".intval($A["id"]).",
					".intval($A["group_id"]).",
					".intval($_POST["room_id"]).",
					".time().",
					'".common()->get_ip()."',
					'".session_id()."',
					'".$_SERVER["HTTP_USER_AGENT"]."',
					'".$_SERVER["HTTP_REFERER"]."',
					'"._es($_POST["color"])."',
					'"._es($_POST["login"])."',
					'".$A["gender"]."',
					'"._es($A["chat_color_1"])."',
					'"._es($A["chat_color_2"])."',
					'"._es($A["chat_color_3"])."',
					'"._es($A["chat_color_4"])."',
					'"._es($A["chat_show_time"])."',
					'"._es($A["chat_refresh"])."',
					'"._es($A["chat_msg_filter"])."',
					".intval($info_status).",
					".time()."
				)\r\n";
			db()->query($sql);
			// Create system message
			$GLOBALS['chat_online'][$A["id"]] = array();
			$this->CHAT_OBJ->_set_system_message($_POST["room_id"], $A["gender"], $_POST["login"], $_POST["color"], 0);
			// Create log record
			$log_array = array (
				"user_id"		=> $A["id"],
				"room_id"		=> $_POST["room_id"],
				"add_date"		=> time(),
				"session_id"	=> session_id(),
				"text_color"	=> $_POST["color"],
				"login"			=> $_POST["login"],
				"gender"		=> $A["gender"],
			);
			$this->CHAT_OBJ->_log_change_online_status($log_array);
			// Redirect user to the main chat page
			js_redirect("./?object=".CHAT_CLASS_NAME);
		} else {
			$body .= common()->_show_error_message();
		}
		return $body;
	}
}
