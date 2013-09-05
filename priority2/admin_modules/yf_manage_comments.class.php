<?php

//-----------------------------------------------------------------------------
// Comments management module
class yf_manage_comments {

	/** @var int Text preview cutoff (set to 0 to disable) */
	public $TEXT_PREVIEW_LENGTH	= 0;
	/** @var bool Filter on/off */
	public $USE_FILTER				= true;
	/** @var array @conf_skip pairs object=comment_action */
	public $_comments_actions	= array(
		"articles"		=> "view",
		"blog"			=> "show_single_post",
		"faq"			=> "view",
		"gallery"		=> "show_medium_size",
		"help"			=> "view_answers",
		"news"			=> "full_news",
		"que"			=> "view",
		"reviews"		=> "view_details",
		"user_profile"	=> "show",
	);

	//-----------------------------------------------------------------------------
	// Constructor
	function yf_manage_comments() {
		main()->USER_ID = $_GET['user_id'];
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Array of boxes
		$this->_boxes = array(
		);
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Get sites info
		$this->_sites_info = main()->init_class("sites_info", "classes/");
		$FIRST_SITE_INFO = array_shift($this->_sites_info->info);
		// Calling function to divide records per pages
		$sql = "SELECT * FROM ".db('comments')." ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY add_date DESC ";
		// Pager
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "./?object=".$_GET["object"]);
		// Get ids
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$comments_array[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		unset($users_ids[""]);
		unset($users_ids[0]);
		// Get users infos
		if (!empty($users_ids)) {
			$users_array = user($users_ids, array("id","name","nick","login","email","ip","add_date","photo_verified"));
		}
		// Process comments
		foreach ((array)$comments_array as $comment_id => $comment_info) {
			if (!empty($this->TEXT_PREVIEW_LENGTH)) {
				$comment_info["text"] = substr($comment_info["text"], 0, $this->TEXT_PREVIEW_LENGTH);
			}
			$user_info = $users_array[$comment_info["user_id"]];
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"comment_id"	=> intval($comment_info["id"]),
				"object_name"	=> _prepare_html($comment_info["object_name"]),
				"object_id"		=> intval($comment_info["object_id"]),
				"text"			=> nl2br(_prepare_html($comment_info["text"])),
				"add_date"		=> _format_date($comment_info["add_date"], "long"),
				"active"		=> intval((bool) $comment_info["active"]),
				"ip"			=> _prepare_html($comment_info["ip"]),
				"user_id"		=> intval($user_info["id"]),
				"user_name"		=> $user_info ? _prepare_html(_display_name($user_info)) : $comment_info["user_name"],
				"user_login"	=> $user_info ? _prepare_html($user_info["login"]) : "",
				"user_nick"		=> $user_info ? _prepare_html($user_info["nick"]) : "",
				"user_email"	=> $user_info ? _prepare_html($user_info["email"]) : "",
				"user_avatar"	=> $user_info ? _show_avatar($user_info["id"], $user_info, 1) : "",
				"profile_url"	=> $user_info ? $user_info["profile_url"] : "",
				"member_url"	=> $user_info ? "./?object=account&action=show&user_id=".$user_info["id"] : "",
				"group_name"	=> $user_info ? $this->_account_types[$user_info["group"]] : "",
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$comment_info["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$comment_info["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate&id=".$comment_info["id"],
				"item_link"		=> "./?object=".$comment_info["object_name"]."&action=".$this->_comments_actions[$comment_info["object_name"]]."&id=".$comment_info["object_id"],
				"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($comment_info["user_id"])),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_delete",
			"total"			=> intval($total),
			"items"			=> $items,
			"pages"			=> $pages,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Edit record
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id"));
		}
		// Try to get record info
		$info = db()->query_fetch("SELECT * FROM ".db('comments')." WHERE id=".intval($_GET["id"]));
		if (empty($info)) {
			return _e(t("No such record"));
		}
		// Try to get given user info
		$user_info = db()->query_fetch("SELECT id,name,nick,photo_verified FROM ".db('user')." WHERE id=".intval($info["user_id"]));
		// Check posted data and save
		if (count($_POST) > 0) {
			if (empty($_POST["text"])) {
				_re(t("Comment text required"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Do update record
				db()->UPDATE("comments", array(
					"text" 			=> _es($_POST["text"]),
				), "id=".intval($info["id"]));
				// Return user back
				return js_redirect($RETURN_PATH, false);
			}
		}
		// Show form
		$replace = array(
			"form_action"		=> $FORM_ACTION,
			"error_message"		=> $error_message,
			"user_id"			=> intval(main()->USER_ID),
			"user_name"			=> _prepare_html(_display_name($user_info)),
			"user_avatar"		=> _show_avatar($info["user_id"], $user_info, 1, 1),
			"user_profile_link"	=> _profile_link($info["user_id"]),
			"user_email_link"	=> _email_link($info["user_id"]),
			"text"				=> _prepare_html($info["text"]),
			"object_name"		=> _prepare_html($info["object_name"]),
			"object_id"			=> intval($info["object_id"]),
			"back_url"			=> "./?object=".$_GET["object"],
			"ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($info["user_id"])),
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	//-----------------------------------------------------------------------------
	// Do delete record (mass method)
	function mass_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items_to_delete"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM ".db('comments')." WHERE id IN(".implode(",",$ids_to_delete).")");
		}
		// Return user back
//		return js_redirect("./?object=".$_GET["object"]);
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	//-----------------------------------------------------------------------------
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM ".db('comments')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Revert record "active" status
	function activate () {
		$_GET["id"] = intval($_GET["id"]);
		// Try to get record info
		if (!empty($_GET["id"])) {
			$info = db()->query_fetch("SELECT * FROM ".db('comments')." WHERE id=".intval($_GET["id"]));
		}
		// Update record (invert active status)
		if (!empty($info)) {
			db()->UPDATE("comments", array("active" => (int)!$info["active"]), "id=".intval($_GET["id"]));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"active"		=> 'select_box("active",		$this->_active_statuses,$selected, 0, 2, "", false)',
			"object_name"	=> 'select_box("object_name",	$this->_object_names,	$selected, 0, 2, "", false)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,	$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
		));
		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}
		// Get user account type
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		// Active statuses
		$this->_active_statuses = array(
			""	=> "-- ALL --",
			1	=> "Active",
			-1	=> "Inactive",
		);
		// Try to get object names from comments
		$this->_object_names[""] = "-- ALL --";
		$Q = db()->query("SELECT DISTINCT(object_name) FROM ".db('comments')." WHERE object_name != ''");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_object_names[$A["object_name"]] = $A["object_name"];
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"id",
			"object_id",
			"object_name",
			"user_id",
			"add_date",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"object_name",
			"object_id",
			"cid_min",
			"cid_max",
			"date_min",
			"date_max",
			"nick",
			"user_id",
			"text",
			"account_type",
			"sort_by",
			"sort_order",
		);
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["cid_min"]) 			$sql .= " AND id >= ".intval($SF["cid_min"])." \r\n";
		if ($SF["cid_max"])			 	$sql .= " AND id <= ".intval($SF["cid_max"])." \r\n";
		if ($SF["date_min"]) 			$sql .= " AND add_date >= ".strtotime($SF["date_min"])." \r\n";
		if ($SF["date_max"])			$sql .= " AND add_date <= ".strtotime($SF["date_max"])." \r\n";
		if ($SF["user_id"])			 	$sql .= " AND user_id = ".intval($SF["user_id"])." \r\n";
		if ($SF["object_id"])		 	$sql .= " AND object_id = ".intval($SF["object_id"])." \r\n";
		if ($SF["object_name"])		 	$sql .= " AND object_name = '"._es($SF["object_name"])."' \r\n";
		if (strlen($SF["text"]))		$sql .= " AND text LIKE '"._es($SF["text"])."%' \r\n";
		if (in_array($SF["active"], array(1,-1))) {
		 	$sql .= " AND active = '".intval($SF["active"] == 1 ? 1 : 0)."' \r\n";
		}
		if (strlen($SF["nick"]) || strlen($SF["account_type"])) {
			if (strlen($SF["nick"])) 	$users_sql .= " AND nick LIKE '"._es($SF["nick"])."%' \r\n";
			if ($SF["account_type"])	$users_sql .= " AND `group` = ".intval($SF["account_type"])." \r\n";
		}
		// Add subquery to users table
		if (!empty($users_sql)) {
			$sql .= " AND user_id IN( SELECT id FROM ".db('user')." WHERE 1=1 ".$users_sql.") \r\n";
		}
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY ".$this->_sort_by[$SF["sort_by"]]." \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based filter
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
		$pheader = t("Manage comments");
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

	function _hook_widget__comments_stats ($params = array()) {
// TODO
	}

	function _hook_widget__comments_latest ($params = array()) {
// TODO
	}
}
