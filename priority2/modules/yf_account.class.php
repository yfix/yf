<?php

// User account management
class yf_account {

	// YF module constructor
	function _init () {
		// Account class name (to allow changing only in one place)
		define("ACCOUNT_CLASS_NAME", "account");
		// Sub modules folder
		define("ACCOUNT_MODULES_DIR", USER_MODULES_DIR.ACCOUNT_CLASS_NAME."/");

		$this->_active_modules = main()->get_data("user_modules");
	}

	// Default function
	function show () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}

		// Try to get unread messages from the admin
		$Q = db()->query("SELECT * FROM ".db('admin_messages')." WHERE user_id=".intval(main()->USER_ID)." AND `read`='0' ORDER BY time DESC");
		while ($A = db()->fetch_assoc($Q)) {
			$admin_messages[] = array(
				"message_id"	=> intval($A["id"]),
				"title"			=> _prepare_html($A["title"]),
				"text"			=> _prepare_html($A["text"]),
				"add_date"		=> _format_date($A["time"], "long"),
				"read_link"		=> "./?object=".$_GET["object"]."&action=read_admin_message&id=".intval($A["id"]),
			);
		}
		// Get suggesting messages
		$suggests = $this->_show_suggesting_messages();
		// Process main template
		$replace = array(
			"user_name"		=> _display_name($this->_user_info),
			"admin_messages"=> $admin_messages,
			"suggests"		=> $suggests,
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}

	// Display suggesting messages
	function _show_suggesting_messages () {
		$user_modules_methods = main()->call_class_method("user_modules", "admin_modules/", "_get_methods", array("private" => "1")); 

		$suggests = array();
		foreach ((array)$user_modules_methods as $module_name => $module_methods) {
			if (!isset($this->_active_modules[$module_name])) {
				continue;
			}
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, 17) != "_account_suggests"){
					continue;
				}
				
				$module_suggests = main()->_execute($module_name, $method_name);
				foreach ((array)$module_suggests as $suggest){
					$suggests[] = $suggest;
				}
			}
		}
		
		if (!empty($suggests)){
			$replace = array(
				"suggests"		=> $suggests,
			);
			
			return tpl()->parse(__CLASS__."/suggests", $replace);
		}
	}

	// Set status "read" for the given admin message
	function read_admin_message () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try ot get message info
		$message_info = db()->query_fetch("SELECT * FROM ".db('admin_messages')." WHERE id=".intval($_GET["id"])." AND user_id=".intval(main()->USER_ID));
		if (empty($message_info["id"])) {
			_re("No such admin message!");
			return _e();
		}
		// Update record
		db()->UPDATE("admin_messages", array("read" => 1), "id=".intval($_GET["id"]));
		// Return user back
		return js_redirect("./?object=".$_GET["object"]);
	}

	function _show_stats () {
		$replace = array(
			"member_since"	=> _format_date($this->_user_info["add_date"]),
			"last_update"	=> $this->_user_info["last_update"] ? _format_date($this->_user_info["last_update"], "long") : t("no_update_yet"),
			"emails"		=> intval($this->_user_info["emails"]),
			"visits"		=> intval($this->_user_info["visits"]),
		);
		return tpl()->parse(__CLASS__."/stats", $replace);
	}

	
	// 
	function favorite_add () {
		$OBJ = $this->_load_sub_module("account_favorites");
		return is_object($OBJ) ? $OBJ->_add() : "";
	}

	// 
	function favorite_delete () {
		$OBJ = $this->_load_sub_module("account_favorites");
		return is_object($OBJ) ? $OBJ->_delete() : "";
	}

	// 
	function edit_favorites () {
		$OBJ = $this->_load_sub_module("account_favorites");
		return is_object($OBJ) ? $OBJ->_edit() : "";
	}

	// 
	function ignore_user () {
		$OBJ = $this->_load_sub_module("account_ignore");
		return is_object($OBJ) ? $OBJ->_ignore() : "";
	}

	// 
	function unignore_user () {
		$OBJ = $this->_load_sub_module("account_ignore");
		return is_object($OBJ) ? $OBJ->_unignore() : "";
	}

	// 
	function edit_ignored () {
		$OBJ = $this->_load_sub_module("account_ignore");
		return is_object($OBJ) ? $OBJ->_edit() : "";
	}

	// Firt step of auto changing email
	function change_email () {
		$OBJ = $this->_load_sub_module("account_change_email");
		return is_object($OBJ) ? $OBJ->_first_step() : "";
	}

	// Email change confirmation step
	function confirm_change_email () {
		$OBJ = $this->_load_sub_module("account_change_email");
		return is_object($OBJ) ? $OBJ->_confirm() : "";
	}

	function _load_sub_module ($module_name = "") {
		$OBJ = main()->init_class($module_name, ACCOUNT_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("ACCOUNT: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}
	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = "Welcome, "._display_name($this->_user_info);
		if ($_GET["action"] == "edit_favorites") {
			$pheader = "Edit favourites";
			$subheader = "";
		} elseif ($_GET["action"] == "edit_ignored") {
			$pheader = "Edit Ignored Users List";
			$subheader = "";
		}
/*
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"						=> "",
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
*/
		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
	
	function _account_suggests(){
		// Get live quick user stats
		$totals = main()->call_class_method("user_stats", "classes/", "_get_live_stats", array("user_id" => $this->_user_info["id"]));
		
		// Prepare suggests
		if ($this->_active_modules["user_info"] && !_avatar_exists(main()->USER_ID)) {
			$suggests[] = '{t(You have not uploaded your avatar image yet.)} {t(If you wish to show other members yourself &#40;or some part of yourself&#41;, please, upload your photo)} <a href="./?object=user_info">{t(here)}</a>.';
		} 
		if ($this->_active_modules["blog"] && !$totals["blog_posts"]) {
			$suggests[] = '{t(You have not started your)} <a href="./?object=blog&action=start">{t(blog)}</a> {t(yet. It is the perfect tool to log and share your life experience, thoughts, joys and sorrows with other like-minded people, make new friends and promote yourself in our community)}. {t(It&#39;s easy to start, just click)} <a href="./?object=blog&action=start">{t(here)}</a>';
		}
		if ($this->_active_modules["interests"] && !$totals["try_interests"]) {
			$suggests[]	= '{t(You have not specified your)} <a href="./?object=interests&action=manage">{t(interests)}</a> {t(yet. Please do so, it won&#39;t take you more than a couple of minutes and will help community members in finding good matches. Click)} <a href="./?object=interests&action=manage">{t(here)}</a>';
		}
		return $suggests;
	}
}
