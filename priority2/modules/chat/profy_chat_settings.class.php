<?php

/**
* Chat Settings
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_chat_settings {

	var $CHAT_OBJ = null;

	/**
	* Chat Settings
	*/
	function profy_chat_settings () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}

	/**
	* Form to edit user settings
	*/
	function _edit() {
		$user_info = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `id`=".intval(CHAT_USER_ID));
		$user_info["chat_refresh"] = intval($user_info["chat_refresh"]);
		// Prepare template
		$replace = array(
			"form_action"		=> "./?object=".CHAT_CLASS_NAME."&action=save_settings",
			"login"				=> CHAT_USER_LOGIN. ($user_info["group_id"] == 1 ? " (".t("Moderator").")" : ""),
			"your_color"		=> CHAT_USER_TEXT_COLOR,
			"chat_color_1"		=> CHAT_USER_BG_COLOR_1 ? CHAT_USER_BG_COLOR_1 : "#ffffff",
			"chat_color_2"		=> CHAT_USER_BG_COLOR_2 ? CHAT_USER_BG_COLOR_2 : "#ffffff",
			"chat_color_3"		=> CHAT_USER_BG_COLOR_3 ? CHAT_USER_BG_COLOR_3 : "#ffffff",
			"chat_color_4"		=> CHAT_USER_BG_COLOR_4 ? CHAT_USER_BG_COLOR_4 : "#ffffff",
			"messages_time_box"	=> common()->select_box("messages_time", array(t("without_seconds"), t("with_seconds"),t("do_not_show")), CHAT_USER_MSG_SHOW_TIME, false),
			"msg_filter_box"	=> common()->select_box("msg_filter", array(t("all_messages"),t("only_private")), CHAT_USER_MSG_FILTER, false),
			"refresh_time_box"	=> common()->select_box("refresh_time", $this->CHAT_OBJ->_refresh_select_array, CHAT_USER_REFRESH_TIME ? CHAT_USER_REFRESH_TIME : $this->CHAT_OBJ->REFRESH_TIME, false),
		);
		return tpl()->parse("chat/edit_settings", $replace);
	}

	/**
	* Save user settings
	*/
	function _save() {
		// Check colors
		for ($i = 1; $i <= 4; $i++) {
			if (!(preg_match('/^#[0-9a-f]{6}$/i', $_POST["chat_color_".$i]) || preg_match('/^[a-z]{3,16}$/i', $_POST["chat_color_".$i]))) {
				common()->_raise_error(t("wrong_color")." : ".$i);
			}
		}
		if (!(preg_match('/^#[0-9a-f]{6}$/i', $_POST["chat_your_color"]) || preg_match('/^[a-z]{3,16}$/i', $_POST["chat_your_color"]))) {
			common()->_raise_error(t("wrong_color"));
		}
		// Chec other settings
		$_POST["messages_time"]	= intval($_POST["messages_time"]);
		if (!in_array($_POST["messages_time"], range(0,2))) {
			common()->_raise_error(t("wrong_messages_time"));
		}
		$_POST["msg_filter"]	= intval($_POST["msg_filter"]);
		if (!in_array($_POST["msg_filter"], range(0,1))) {
			common()->_raise_error(t("wrong_messages_filter"));
		}
		$_POST["refresh_time"]	= intval($_POST["refresh_time"]);
		if (!in_array($_POST["refresh_time"], $this->CHAT_OBJ->_refresh_select_array)) {
			common()->_raise_error(t("wrong_refresh_time"));
		}
		// Continue if no errors occured
		if (!common()->_error_exists()) {
			$sql = "UPDATE `".db('chat_online')."` SET 
					`chat_color_1` = '"._es($_POST["chat_color_1"])."',
					`chat_color_2` = '"._es($_POST["chat_color_2"])."',
					`chat_color_3` = '"._es($_POST["chat_color_3"])."',
					`chat_color_4` = '"._es($_POST["chat_color_4"])."',
					`text_color`='"._es($_POST["chat_your_color"])."',
					`chat_show_time`=".$_POST["messages_time"].",
					`chat_refresh`=".$_POST["refresh_time"].",
					`chat_msg_filter` = ".$_POST["msg_filter"]."
					WHERE `user_id`=".intval(CHAT_USER_ID);
			db()->query($sql);
			$sql = "UPDATE `".db('chat_users')."` SET 
					`chat_color_1` = '"._es($_POST["chat_color_1"])."',
					`chat_color_2` = '"._es($_POST["chat_color_2"])."',
					`chat_color_3` = '"._es($_POST["chat_color_3"])."',
					`chat_color_4` = '"._es($_POST["chat_color_4"])."',
					`chat_show_time` = ".$_POST["messages_time"].",
					`chat_refresh` = ".$_POST["refresh_time"].",
					`chat_msg_filter` = ".$_POST["msg_filter"]."
				WHERE `id`=".intval(CHAT_USER_ID);
			db()->query($sql);
			// Special code for the Opera
			if (IS_OPERA) {
				$body .= "<script>window.opener.parent.window.location.assign('./?object=".CHAT_CLASS_NAME."');</script>\r\n";
			} else {
				$body .= "<script>window.opener.CHAT_VARS['own_color']			= '".$_POST["chat_your_color"]."';</script>\r\n";
				$body .= "<script>window.opener.CHAT_VARS['user_msg_show_time']	= '".$_POST["messages_time"]."';</script>\r\n";
				$body .= "<script>window.opener.CHAT_VARS['refresh']			= ".($_POST["refresh_time"] * 1000).";</script>\r\n";
				$body .= "<script>window.opener.PROFY_CHAT._set_refresh_time();</script>\r\n";
				for ($i = 1; $i <= 4; $i++) {
					$body .= "<script>window.opener.CHAT_VARS['user_color_".$i."'] = '".$_POST["chat_color_".$i]."';</script>\r\n";
				}
			}
			$body .= "<script>window.close();</script>\r\n";
			$body .= "<script>alert('".t("save_successful")."');</script>\r\n";
		} else {
			$body .= common()->_show_error_message();
		}
		return $body;
	}

	/**
	* Set new ignore status for the selected user
	*/
	function _set_ignore() {
		if (!empty($_GET["user_id"])) {
			// Check if ignoring user exists
			$A = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `id`=".intval($_GET["user_id"]));
			// Check if user is online
			if ($A["id"] && is_array($GLOBALS['chat_online'])) {
				$A3 = $GLOBALS['chat_online'][$A["id"]];
			}
		}
		if (!empty($A['id']) && !empty($A3["user_id"])) {
			// Check if user is already in ignore list
			$A2 = db()->query_fetch("SELECT * FROM `".db('chat_ignore')."` WHERE `user_id`=".intval(CHAT_USER_ID)." AND `user_ignore`=".intval($A["id"]));
			// Delete user from ignore list if exists there
			if ($A2['user_id']) {
				$sql = "DELETE FROM `".db('chat_ignore')."` WHERE `user_id`=".intval(CHAT_USER_ID)." AND `user_ignore`=".intval($A["id"]);
				db()->query($sql);
				$ignore = 0;
			// Add user to ignore list
			} else {
				$sql = "INSERT INTO `".db('chat_ignore')."` (
						`user_id`,
						`user_ignore`,
						`add_date`
					) VALUES (
						".intval(CHAT_USER_ID).",
						".intval($A['id']).",
						".time()."
					)\r\n";
				db()->query($sql);
				$ignore = 1;
			}
			$users_add = "new Array(".$A3["user_id"].",\"".$A3["gender"]."\",\"".$A3["text_color"]."\",".intval($ignore).",\"".$A3["login"]."\",".intval($A3["info_status"]).")";
			echo "<script>parent.window.PROFY_CHAT.users_del('".$A["id"]."');</script>\r\n";
			echo "<script>parent.window.PROFY_CHAT.users_add(new Array(".$users_add."));</script>\r\n";
		}
		return $body;
	}
}
