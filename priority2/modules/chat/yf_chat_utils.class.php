<?php

/**
* Chat Utils
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_chat_utils {

	var $CHAT_OBJ = null;

	/**
	* Chat Utils
	*/
	function yf_chat_utils () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}

	/**
	* Store log info about user login or logout (if $login_date is specified - then it is logout)
	*/
	function _log_change_online_status ($A, $login_date = 0) {
		$login_date = intval($login_date);
		// Try to find record when user is logged in (to complete log with logout date)
		if ($login_date) $A2 = db()->query_fetch("SELECT `id` FROM `".db('chat_log_online')."` WHERE `login_date`=".$login_date." AND `logout_date`=0");
		// Check if log record exists
		if ($A2["id"]) {
			$sql = "UPDATE `".db('chat_log_online')."` SET `logout_date` = ".time()."	WHERE `id`=".$A2["id"];
		} else {
			$sql = "INSERT INTO `".db('chat_log_online')."` (
					`user_id`,
					`room_id`,
					`".($login_date ? "logout_date" : "login_date")."`,
					`ip`,
					`session_id`,
					`user_agent`,
					`referer`,
					`text_color`,
					`login`,
					`gender`
				) VALUES (
					".intval($A["user_id"]).",
					".intval($A["room_id"]).",
					".intval($login_date ? $login_date : $A["add_date"]).",
					'".common()->get_ip()."',
					'".$A["session_id"]."',
					'".$_SERVER["HTTP_USER_AGENT"]."',
					'".$_SERVER["HTTP_REFERER"]."',
					'"._es($A["text_color"])."',
					'"._es($A["login"])."',
					'".$A["gender"]."'
				)\r\n";
		}
		db()->query($sql);
	}

	/**
	* Move old records to archive (older 2 days)
	*/
	function _move_old_records_to_archive ($old_days = 2) {
		// Archive common messages
		db()->query("REPLACE DELAYED INTO `".db('chat_archive_messages')."` SELECT * FROM `".db('chat_messages')."` WHERE `add_date` < ".(time() - $old_days*24*3600));
		db()->query("DELETE LOW_PRIORITY FROM `".db('chat_messages')."` WHERE `add_date` < ".(time() - 2*24*3600));
		// Archive private messages
		db()->query("REPLACE DELAYED INTO `".db('chat_archive_private')."` SELECT * FROM `".db('chat_private')."` WHERE `add_date` < ".(time() - $old_days*24*3600));
		db()->query("DELETE LOW_PRIORITY FROM `".db('chat_private')."` WHERE `add_date` < ".(time() - 2*24*3600));
		// Archive log_online db table
		db()->query("REPLACE DELAYED INTO `".db('chat_archive_log_online')."` SELECT * FROM `".db('chat_log_online')."` WHERE `login_date` < ".(time() - 31*24*3600));
		db()->query("DELETE LOW_PRIORITY FROM `".db('chat_log_online')."` WHERE `login_date` < ".(time() - 2*24*3600));
	}

	/**
	* All registered users list
	*/
	function _show_users_list() {
		$Q = db()->query("SELECT * FROM `".db('chat_users')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$replace = array(
			);
// TODO : need to add code
			$items .= tpl()->parse("chat/users_list_main", $replace);
		}
		$body = strlen($items) ? tpl()->parse("chat/users_list_main", array("items" => $items)) : t("no_users");
		$this->show_empty_page($body);
	}

	/**
	* Show chat messages log for the selected period
	*/
	function _show_messages_log() {
// TODO : need to add code
		$start_date	= time() - 31*24*3600;
		$end_date	= time();
/*
		$Q = db()->query("SELECT * FROM `".db('chat_messages')."` WHERE `add_date` BETWEEN ".intval($start_date)." AND ".intval($end_date));
		while ($A = db()->fetch_assoc($Q)) {
			$replace = array(
			);
			$items .= tpl()->parse("chat/users_list_main", $replace);
		}
		$body = strlen($items) ? tpl()->parse("chat/users_list_main", array("items" => $items)) : t("no_users");
		$this->show_empty_page($body);
*/
	}
}
