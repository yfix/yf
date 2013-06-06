<?php

/**
* Chat Get
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_chat_get {

	var $CHAT_OBJ = null;

	/**
	* Chat Get
	*/
	function yf_chat_get () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}
	
	/**
	* Show commands
	*/
	function _show_commands() {
		// Update lst user visit
		db()->query("UPDATE `".db('chat_online')."` SET `last_visit`=".time()." WHERE `user_id`=".intval(CHAT_USER_ID));
		// USERS
		$k_users_string = trim(!empty($_POST["known_users"]) ? $_POST["known_users"] : $_GET["known_users"]);
		if (!empty($k_users_string)) {
			$known_users = explode(",", $k_users_string);
			$known_users = array_filter($known_users);
		}
		foreach ((array)$known_users as $k => $v) {
			$known_users[$k] = $v = intval($v);
			if ($v) {
				$users_to_delete[$v] = $v;
			}
		}
		foreach ((array)$GLOBALS['chat_online'] as $A5) {
			if ($A5['room_id'] == CHAT_USER_ROOM_ID) {
				$online_users[$A5["user_id"]] = $A5;
			}
		}
		// Process online users (in the current room)
		foreach ((array)$online_users as $user_id => $A) {
			if ($users_to_delete[$A["user_id"]]) {
				unset($users_to_delete[$A["user_id"]]);
			}
			if (isset($known_users[$user_id])) {
				continue;
			}
			// Skip current user
			if ($user_id == CHAT_USER_ID) {
				continue;
			}
			// Create user XML item string
			$users_add .= "<user ".
				"user_id=\"".intval($A["user_id"])."\" ".
				"gender=\""._prepare_html($A["gender"])."\" ".
				"text_color=\""._prepare_html($A["text_color"])."\" ".
				"ignore_list=\"".intval($this->CHAT_OBJ->ignore_list[$A["user_id"]])."\" ".
				"user_login=\""._prepare_html($A["login"])."\" ".
				"info_status=\"".intval($A["info_status"])."\" ".
				"group_id=\"".($A["group_id"] == 1 ? $A["group_id"] : "")."\" ".
				"/>\n";
		}
		// Create string of user ids to delete from list
		if (!empty($users_to_delete)) {
			$this->CHAT_OBJ->_add_client_cmd("users_del", implode(",", $users_to_delete));
		}
		if (!empty($users_add)) {
			$this->CHAT_OBJ->_add_client_cmd("users_add", $users_add);
		}
		// Total users in all rooms (only if there were changes)
		if (!empty($this->CHAT_OBJ->_CLIENT_CMDS)) {
			$this->CHAT_OBJ->_add_client_cmd("online_total", count($GLOBALS['chat_online']));
		}
		// MESSAGES
		if (!CHAT_USER_MSG_FILTER) {
			$msg_add = $this->_get_messages(0);
			if (!empty($msg_add)) {
				$this->CHAT_OBJ->_add_client_cmd("msgs_add", $msg_add);
			}
		}
		// PRIVATE MESSAGES
		$private_add = $this->_get_private(0);
		if (!empty($private_add)) {
			$this->CHAT_OBJ->_add_client_cmd("priv_add", $private_add);
		}
	}

	/**
	* Messages
	*/
	function _get_messages($first_time = true, $AS_JS_ARRAY = false) {
		if (!CHAT_USER_ID) {
			return false;
		}
		// Create additional condition for the not first time query
		if ($first_time) {
			$add_sql = " ORDER BY `add_date` DESC LIMIT ".intval($this->CHAT_OBJ->FIRST_SHOW_MSGS);
		} else {
			$add_sql = " AND `add_date`>".intval(CHAT_USER_LAST_VISIT - $this->CHAT_OBJ->REFRESH_TIME - $this->CHAT_OBJ->OFFLINE_TTL)." ORDER BY `add_date` DESC";
		}
		// Select messages from db
		$Q = db()->query("SELECT * FROM `".db('chat_messages')."` WHERE `room_id`=".intval(CHAT_USER_ROOM_ID)." ".$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			// Skip messages if user is ignored
			if ($this->CHAT_OBJ->ignore_list[$A["user_from"]]) {
				continue;
			}
			$messages[$A["id"]] = $A;
		}
		// Process messages
		if (is_array($messages)) {
			$messages = array_reverse($messages);
		}
		foreach ((array)$messages as $A) {
			if ($AS_JS_ARRAY) {
				$msgs_array[] = "{".
					"'msg_id'		:".intval($A["id"]).",".
					"'user_login'	:\""._prepare_html($A["login"])."\",".
					"'add_date'		:".intval($A["add_date"]).",".
					"'text_color'	:\""._prepare_html($A["text_color"])."\",".
					"'text'			:\""._prepare_html($A["text"])."\"".
					"}";
			} else {
				$msgs_add .= "<msg ".
					"msg_id=\"".intval($A["id"])."\" ".
					"user_login=\""._prepare_html($A["login"])."\" ".
					"add_date=\"".intval($A["add_date"])."\" ".
					"text_color=\""._prepare_html($A["text_color"])."\" ".
					"text=\""._prepare_html($A["text"])."\" ".
					"/>\n";
			}
		}
		if ($AS_JS_ARRAY && !empty($msgs_array)) {
			$msgs_add = str_replace(array("\r","\n","\t"), "", implode(",",$msgs_array));
		}
		return $msgs_add;
	}

	/**
	* Private messages
	*/
	function _get_private($first_time = true, $AS_JS_ARRAY = false) {
		if (!CHAT_USER_ID) {
			return false;
		}
		// Create additional condition for the not first time query
		if ($first_time) {
			$add_sql = " AND `add_date` > ".(time() - $this->CHAT_OBJ->FIRST_PRIVATE_TTL)." ORDER BY `add_date` DESC LIMIT ".intval($this->CHAT_OBJ->FIRST_SHOW_MSGS);
		} else {
			$add_sql =  " AND `add_date`>".intval(CHAT_USER_LAST_VISIT - $this->CHAT_OBJ->REFRESH_TIME - $this->CHAT_OBJ->OFFLINE_TTL)." ORDER BY `add_date` DESC";
		}
		// Select messages from db
		$Q = db()->query("SELECT * FROM `".db('chat_private')."` WHERE (`user_from`=".intval(CHAT_USER_ID)." OR `user_to`=".intval(CHAT_USER_ID).") AND `room_id`=".intval(CHAT_USER_ROOM_ID)." ".$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			// Skip messages if user is ignored
			if ($this->CHAT_OBJ->ignore_list[$A["user_from"]]) {
				continue;
			}
			$messages[$A["id"]] = $A;
		}
		// Process messages
		if (is_array($messages)) {
			$messages = array_reverse($messages);
		}
		foreach ((array)$messages as $A) {
			$msg_from_you = $A["user_from"] == CHAT_USER_ID ? 1 : 0;
			if ($AS_JS_ARRAY) {
				$private_array[] = "{".
					"'msg_id'		:".intval($A["id"]).",".
					"'user_login'	:\""._prepare_html($msg_from_you ? $A["login_to"] : $A["login_from"])."\",".
					"'add_date'		:".intval($A["add_date"]).",".
					"'text_color'	:\""._prepare_html($A["text_color"])."\",".
					"'text'			:\""._prepare_html($A["text"])."\",".
					"'msg_from_you'	:".intval($msg_from_you).
					"}";
			} else {
				$private_add .= "<private ".
					"msg_id=\"".intval($A["id"])."\" ".
					"user_login=\""._prepare_html($msg_from_you ? $A["login_to"] : $A["login_from"])."\" ".
					"add_date=\"".intval($A["add_date"])."\" ".
					"text_color=\""._prepare_html($A["text_color"])."\" ".
					"text=\""._prepare_html($A["text"])."\" ".
					"msg_from_you=\"".intval($msg_from_you)."\" ".
					"/>\n";
			}
		}
		if ($AS_JS_ARRAY && !empty($private_array)) {
			$private_add = str_replace(array("\r","\n","\t"), "", implode(",",$private_array));
		}
		return $private_add;
	}
}
