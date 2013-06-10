<?php

//-----------------------------------------------------------------------------
// Members management module
class yf_members {

	// User id
	public $USER_ID = null;
	// Session array name where filter vars are stored
	public $_filter_name = "members_filter";
	// Filter on/off
	public $USE_FILTER = true;

	//-----------------------------------------------------------------------------
	// Constructor
	function _init() {
		$this->USER_ID = $_GET['user_id'];
		if (!$this->USE_FILTER) {
			return true;
		}
		$this->_boxes = array(
			"state"			=> 'select_box("state",			$this->_states,			$selected, " ", 2, "", false)',
			"country"		=> 'select_box("country",		$this->_countries,		$selected, 0, 2, "", false)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types,	$selected, 0, 2, "", 1)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
			"ad_status"		=> 'select_box("ad_status",		$this->_ad_statuses,	$selected, " ", 2, "", false)',
			"ad_type"		=> 'select_box("ad_type",		$this->_ad_types,		$selected, " ", 2, "", false)',
			"group"			=> 'select_box("group",			$this->_user_groups,	$selected, false, 2, "", false)',
		);
		$this->_user_groups = array();
		// Fill array of admin groups
		$Q = db()->query("SELECT `id`,`name` FROM `".db('user_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			// Skip guest
			if ($A['id'] == 1) {
				continue;
			}
			$this->_user_groups[$A['id']] = $A['name'];
		}
		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}
		// Get user account type
		$this->_account_types = array("" => "All");
		foreach ((array)main()->get_data("account_types") as $k => $v) {
			$this->_account_types[$k] = $v;
		}
		// Available ad types
		$this->_ad_types	= array(
			"free"	=> t("Free"),
			"paid"	=> t("Paid"),
		);
		// Available ad statuses
		$this->_ad_statuses	= array(
			"waiting"	=> t("Waiting"),
			"active"	=> t("Active"),
			"expired"	=> t("Expired"),
			"suspended"	=> t("Suspended"),
		);
		// Sort fields
		$this->_sort_by = array(
			"",
			"id",
			"login",
			"email",
			"ip",
			"add_date",
		);
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Fields in the filter
		$this->_fields_in_filter = array(
			"id_min",
			"id_max",
			"name",
			"nick",
			"email",
			"login",
			"password",
			"country",
			"state",
			"account_type",
			"phone",
			"sort_by",
			"sort_order",
			"plblog_only",
		);
	}

	/**
	* Activate user account
	*/
	function activate() {
		// Try to find such menu in db
		if (!empty($_GET["id"])) {
			$user_info = user($_GET["id"]);
		}
		// Do change activity status
		if (!empty($user_info)) {
			update_user($user_info["id"], array("active" => (int)!$user_info["active"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("user");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($user_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql = "SELECT * FROM `".(main()->USER_INFO_DYNAMIC ? db('user_data_main') : db('user'))."`".$filter_sql;
// TODO: connect filter again
//		$sql = search_user(array("WHERE" => array()), "full", true);
		list($add_sql, $pages, $total) = common()->divide_pages(preg_replace("/ORDER BY .*\$/ims", "", $sql));
		if (!$filter_sql) {
			$sql .= " ORDER BY `id` DESC";
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
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete_account&id=".$user_info["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"add_link"			=> "./?object=".$_GET["object"]."&action=add",
			"num_members"	=> intval($total),
			"items"			=> $items,
			"pages"			=> $pages,
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
		);
		$body = tpl()->parse($_GET["object"]."/main", $replace);
		return $body;
	}

	/**
	* Form to edit user group
	*/
	function add() {
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
	}
	function edit() {
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
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$MF = &$_SESSION[$this->_filter_name];
		foreach ((array)$MF as $k => $v) $MF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($MF["id_min"]) 				$sql .= " AND `id` >= ".intval($MF["id_min"])." \r\n";
		if ($MF["id_max"])			 	$sql .= " AND `id` <= ".intval($MF["id_max"])." \r\n";
		if (strlen($MF["name"])) 		$sql .= " AND `name` LIKE '"._es($MF["name"])."%' \r\n";
		if (strlen($MF["nick"])) 		$sql .= " AND `nick` LIKE '"._es($MF["nick"])."%' \r\n";
		if (strlen($MF["email"])) 		$sql .= " AND `email` LIKE '"._es($MF["email"])."%' \r\n";
		if (strlen($MF["login"])) 		$sql .= " AND `login` LIKE '"._es($MF["login"])."%' \r\n";
		if (strlen($MF["password"])) 	$sql .= " AND `password` LIKE '"._es($MF["password"])."%' \r\n";
		if ($MF["account_type"])		$sql .= " AND `group` = ".intval($MF["account_type"])." \r\n";
		if (strlen($MF["state"]))		$sql .= " AND `state` = '".$MF["state"]."' \r\n";
		if ($MF["country"])	 			$sql .= " AND `country` = '"._es($MF["country"])."' \r\n";
		if (strlen($MF["phone"]))		$sql .= " AND "._get_phone_search_sql($MF["phone"], "phone")." \r\n";
		if ($MF["plblog_only"])			$sql .= " AND `old_id` != 0 \r\n";
		// Sorting here
		if ($MF["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$MF["sort_by"]]."` \r\n";
		if ($MF["sort_by"] && strlen($MF["sort_order"])) 	$sql .= " ".$MF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based members filter form stored in $_SESSION[$this->_filter_name][...]
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	//-----------------------------------------------------------------------------
	// Filter save method
	function save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Delete all user account information 
	*/
	function delete_account() {
		$user_id = intval($_GET["id"]);
		if (!$user_id) {
			return false;
		}

		$hook_func_name = "_on_delete_account";

		// Get user modules
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
			// Init dir class
			$DIR_OBJ = main()->init_class("dir", "classes/");
			$DIR_OBJ->delete_dir($user_folder_path, true);
		}

		// Delete record from table 'users'
		db()->query("DELETE FROM `".db('user')."` WHERE `id`=".$user_id);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}


	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Members");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
		);			 		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
