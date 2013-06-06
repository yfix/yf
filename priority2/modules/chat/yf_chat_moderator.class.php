<?php

/**
* Chat Moderator
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_chat_moderator {

	var $CHAT_OBJ = null;

	/**
	* Chat Moderator
	*/
	function yf_chat_moderator () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}

	/**
	* Edit ban list control (ONLY FOR MODERATORS!)
	*/
	function _edit_ban_list() {
//		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) return $this->_logout_redirect();
		// Get full ban list from db
		$Q = db()->query("SELECT * FROM `".db('chat_ban_list')."`");
		while ($A = db()->fetch_assoc($Q)) $ban_list[$A["id"]] = $A;
		// Get moderators and users detailed info
		if (!empty($ban_list)) {
			foreach ((array)$ban_list as $A) {
				$moderators_ids[$A["moderator"]] = $A["moderator"];
				if ($A["type"] == "user") $users_ids[$A["value"]] = $A["value"];
			}
			if (!empty($users_ids)) {
				// Get moderators
				$Q2 = db()->query("SELECT * FROM `".db('chat_users')."` WHERE `id` IN (".implode(",",$moderators_ids).")");
				while ($A2 = db()->fetch_assoc($Q2)) $moderators[$A2["id"]] = "<a href='./?object=".CHAT_CLASS_NAME."&action=show_user_info&user_id=".$A2["id"]."' target='_blank'>"._prepare_html($A2["login"])."</a>";
				// Get users
				$Q3 = db()->query("SELECT * FROM `".db('chat_users')."` WHERE `login` IN ('".implode("','",$users_ids)."')");
				while ($A3 = db()->fetch_assoc($Q3)) $users[$A3["login"]] = "<a href='./?object=".CHAT_CLASS_NAME."&action=show_user_info&user_id=".$A3["id"]."' target='_blank'>"._prepare_html($A3["login"])."</a>";
			}
		}
		// Process ban list items
		if (!empty($ban_list)) foreach ((array)$ban_list as $A) {
			$replace2 = array(
				"id"			=> $A["id"],
				"type"			=> $A["type"],
				"value"			=> $A["type"] == "user" ? $users[$A["value"]] : $A["value"],
				"expiration"	=> $A["expiration"] ? date("H:i:s d/m/Y", $A["expiration"]) : t("forever"),
				"moderator"		=> $moderators[$A["moderator"]],
				"add_date"		=> date("H:i:s d/m/Y", $A["expiration"]),
				"ban_delete_url"=> "./?object=".CHAT_CLASS_NAME."&action=delete_ban_item&id=".$A["id"],
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
			);
			$items .= tpl()->parse("chat/edit_ban_list_item", $replace2);
		}
		$replace = array(
			"items"			=> $items,
			"form_action"	=> "./?object=".CHAT_CLASS_NAME."&action=do_ban_user",
		);
		$body = tpl()->parse("chat/edit_ban_list_main", $replace);
//		$this->show_empty_page($body);
		return $body;
	}
	
	/**
	* Delete ban list item (ONLY FOR MODERATORS!)
	*/
	function _delete_ban_item() {
//		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) return $this->_logout_redirect();
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) db()->query("DELETE FROM `".db('chat_ban_list')."` WHERE `id`=".$_GET["id"]);
		js_redirect("./?object=".CHAT_CLASS_NAME."&action=edit_ban_list");
	}
	
	/**
	* Add item to the ban list (ONLY FOR MODERATORS!)
	*/
	function _do_ban_user() {
//		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) return $this->_logout_redirect();
		$_GET['user_id'] = intval($_GET['user_id']);
		$error = false;
		if (isset($_POST["ip"])) {
			$_POST["minutes"] = intval($_POST["minutes"]);
			// Filter wrong IP addresses
			$_POST["ip"] = trim(long2ip(ip2long($_POST["ip"])));
			if (strlen($_POST["ip"])) {
				$type		= "ip";
				$value		= $_POST["ip"];
				$expiration = $_POST["minutes"] ? (time() + $_POST["minutes"] * 60) : 0;
				echo "<script>alert('Banned IP \"".$_POST["ip"]."\"');</script>\r\n";
				echo "<script>window.location.href='./?object=".CHAT_CLASS_NAME."&action=edit_ban_list';</script>\r\n";
			} else $error = true;
		} elseif ($_GET['user_id']) {
			$_GET["minutes"] = intval($_GET["minutes"]);
			$A = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `id`=".intval($_GET["user_id"]));
			if ($A["id"]) {
				$type		= "user";
				$value		= _prepare_html($A["login"]);
				$expiration = $_GET["minutes"] ? (time() + $_GET["minutes"] * 60) : 0;
				echo "<script>alert('Banned user \""._prepare_html($A['login'])."\"');</script>\r\n";
			} else $error = true;
		} else $error = true;
		// If no errors - continue
		if (!$error) {
			$sql = "INSERT INTO `".db('chat_ban_list')."` (
					`type`,
					`value`,
					`expiration`,
					`moderator`,
					`add_date`
				) VALUES (
					'".$type."',
					'"._es($value)."',
					".intval($expiration).",
					".intval(CHAT_USER_ID).",
					".time()."
				)\r\n";
			db()->query($sql);
		}
	}
	
	/**
	* Delete selected message (ONLY FOR MODERATORS!)
	*/
	function _do_ban_message() {
//		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) return $this->_logout_redirect();
		$_GET["msg_id"] = intval($_GET["msg_id"]);
		if ($_GET['msg_id']) {
			$A = db()->query_fetch("SELECT `id` FROM `".db('chat_messages')."` WHERE `id`=".$_GET["msg_id"]);
			if ($A["id"]) {
				db()->query("DELETE FROM `".db('chat_messages')."` WHERE `id`=".$_GET["msg_id"]);
				echo "<script>alert('Deleted message with ID = ".$A['id']."');</script>\r\n";
			}
		}
	}
}
