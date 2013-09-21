<?php

/**
*/
class yf_manage_users {

	/**
	*/
	function show () {
/*
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql = "SELECT * FROM ".(main()->USER_INFO_DYNAMIC ? db('user_data_main') : db('user'))."".$filter_sql;
// TODO: connect filter again
//		$sql = search_user(array("WHERE" => array()), "full", true);
		list($add_sql, $pages, $total) = common()->divide_pages(preg_replace("/ORDER BY .*\$/ims", "", $sql));
		if (!$filter_sql) {
			$sql .= " ORDER BY id DESC";
		}
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$users_array[$A["id"]]	= $A;
			$users_ids[$A["id"]]	= $A["id"];
		}
		foreach ((array)$users_array as $cur_user_id => $user_info) {
			$user_avatar_path = SITE_AVATARS_DIR. $user_info["id"]. ".jpg";
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"user_id"		=> $user_info["id"],
				"user_name"		=> $user_info["name"],
				"login"			=> $user_info["login"],
				"email"			=> $user_info["email"],
				"member_url"	=> "./?object=user_info&user_id=".$user_info["id"],
				"ip"			=> $user_info["ip"],
				"add_date"		=> _format_date($user_info["add_date"]),
				"edit_ads_url"	=> "./?object=manage_ads&user_id=".$user_info["id"],
				"profile_url"	=> $user_info["profile_url"],
				"active"		=> intval((bool) $user_info["active"]),
				"user_avatar"	=> _show_avatar($user_info["id"], $user_info, 1),
				"group_name"	=> t($this->_account_types[$user_info["group"]]),
				"nick"			=> _prepare_html($user_info["nick"]),
				"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($user_info["id"])),
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate&id=".$user_info["id"],
				"edit_url"		=> "./?object=".$_GET["object"]."&action=edit&id=".$user_info["id"],
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete&id=".$user_info["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"add_link"			=> "./?object=".$_GET["object"]."&action=add",
			"num_members"	=> intval($total),
			"items"			=> $items,
			"pages"			=> $pages,
		);
		$body = tpl()->parse($_GET["object"]."/main", $replace);
		return $body;
*/
// TODO
	}

	/**
	*/
	function add() {
/*
		// Save posted data
		if (!empty($_POST)) {
			$_POST["login"] = preg_replace("/[^a-z0-9\_\-\.]/ims", "", $_POST["login"]);
			if (!$_POST["login"]) {
				_re("Login required!");
			}
			if (!strlen($_POST["password"])) {
				_re("Password required!");
			}
			if (!common()->_error_exists()) {
				$_new_pswd = md5($_POST["password"]);
				$_POST = _es($_POST);
				$sql = array(
					"login"			=> $_POST["login"],
					"password"		=> $_new_pswd,
					"name"	=> $_POST["name"],
					"login"	=> $_POST["login"],
					"nick"	=> $_POST["nick"],
					"go_after_login"=> $_POST["go_after_login"],
					"group"			=> intval($_POST["group"]),
					"active"		=> intval($_POST["active"]),
					"add_date"		=> time(),
				);
				db()->INSERT("user", $sql);
				$NEW_ID = db()->INSERT_ID();
				return js_redirect("./?object=".$_GET["object"].($NEW_ID ? "&action=edit&id=".$NEW_ID : ""));
			}
		}
		if (!isset($_POST["active"])) {
			$_POST["active"] = 1;
		}
		$_POST = _prepare_html($_POST);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"error_message"	=> _e(),
			"for_edit"		=> 0,
			"login"			=> $_POST["login"],
			"password"		=> $_POST["password"],
			"name"	=> $_POST["name"],
			"nick"		=> $_POST["nick"],
			"go_after_login"=> $_POST["go_after_login"],
			"group_box"		=> $this->_box("group", $_POST["group"]),
			"active_box"	=> $this->_box("active", $_POST["active"]),
			"back_link"		=> "./?object=".$_GET["object"],
			"groups_link"	=> "./?object=admin_groups",
		);
		return tpl()->parse($_GET["object"]."/add", $replace);
*/
// TODO
	}

	/**
	*/
	function edit() {
/*
		$_GET['id'] = intval($_GET['id']);
		if (!$_GET["id"]) {
			return "No id!";
		}
		// Get current record
		$user_info = user($_GET["id"]);
		// Save posted data
		if (!empty($_POST)) {
			if (!common()->_error_exists()) {
				update_user($_GET["id"], array(
					"group"				=> intval($_POST["group"]),
					"go_after_login"	=> _es($_POST["go_after_login"]),
				));
			}
	 		return js_redirect("./?object=".$_GET["object"]);
		}
		$DATA = $user_info;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		$DATA = _prepare_html($DATA);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"error_message"	=> _e(),
			"for_edit"		=> 1,
			"login"			=> $DATA["login"],
			"group_box"		=> $this->_box("group", $DATA["group"]),
			"groups_link"	=> "./?object=user_groups",
			"go_after_login"=> _prepare_html($DATA["go_after_login"]),
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
*/
// TODO
	}

	/**
	*/
	function activate() {
		if (!empty($_GET["id"])) {
			$user_info = user($_GET["id"]);
		}
		if (!empty($user_info)) {
			update_user($user_info["id"], array("active" => (int)!$user_info["active"]));
		}
		cache()->refresh("user");
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($user_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Delete all user account information 
	*/
	function delete() {
		$user_id = intval($_GET["id"]);
		if (!$user_id) {
			return false;
		}

		$hook_func_name = "_on_delete_account";

		$_user_modules = module("user_modules")->_get_modules();
		$_user_modules_methods = module("user_modules")->_get_methods(array("private" => 1));
		$_modules_where_exists = array();
		foreach  ((array)$_user_modules_methods as $module_name => $methods) {
			if (!in_array($hook_func_name, $methods)) {
				continue;
			}
			$_modules_where_exists[$module_name] = $module_name;
		}
		foreach ((array)$_modules_where_exists as $_module_name) {
			$m = module($_module_name);
			if (method_exists($m, $hook_func_name)) {
				$result = $m->$hook_func_name(array("user_id" => $user_id));
			}
		}

		$user_info = user($user_id);
		$domains = main()->get_data("domains");
		if ($user_info["login"] && $user_info["domain"]) {
			$user_folder_name = $user_info["login"].".".$domains[$user_info["domain"]];
		}
		if ($user_folder_name) {
			$user_folder_path = INCLUDE_PATH."users/".$user_folder_name."/";
		}
		if ($user_folder_path && file_exists($user_folder_path)) {
			_class("dir")->delete_dir($user_folder_path, true);
		}
		db()->query("DELETE FROM ".db('user')." WHERE id=".$user_id);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* User account confirmation
	*/
	function do_confirm () {
// TODO
/*
		if (!strlen($_POST["login"])) {
			_re(t("Login required"));
		}
		if (!common()->_error_exists()) {
			$A = db()->query_fetch("SELECT * FROM ".db('user')." WHERE active='0' AND login='"._es($_POST["login"])."'");
			if (!$A["id"]) _re(t("Sorry, either someone has already confirmed membership or some important information has been missed. Please enter email below and submit"));
		}
		// Continue if check passed
		if (!common()->_error_exists()) {
			// Send email to the confirmed user
			$replace2 = array(
				"name"		=> _display_name($A),
				"email"		=> $A["email"],
				"password"	=> $A["password"],
			);
			$message = tpl()->parse($_GET['object']."/email", $replace2);
			// Set user confirmed
			db()->query("UPDATE ".db('user')." SET active='1' WHERE id=".intval($A["id"]));
			common()->send_mail(SITE_ADVERT_NAME, SITE_ADMIN_EMAIL, $A["email"], _display_name($A), "Thank you for registering with us!", $message, nl2br($message));
			$replace = array(
				"name"	=> _display_name($A),
			);
			$body = tpl()->parse($_GET['object']."/confirmed", $replace);
		} else {
			$body .= _e();
			$body .= $this->show($_POST);
		}
		return $body;
*/
	}

	/**
	*/
	function _show_filter () {
// TODO
	}

	/**
	*/
	function _hook_widget__members_stats ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__members_latest ($params = array()) {
// TODO
	}
}
