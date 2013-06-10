<?php

//-----------------------------------------------------------------------------
// Admin messages handling class
class yf_admin_messages {

	// Filter on/off
	public $USE_FILTER = true;

	//-----------------------------------------------------------------------------
	// Constructor
	function yf_admin_messages () {
		$this->USER_ID = intval($_GET['user_id']);
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
		// Show all messages if no user selected
		if (empty($this->USER_ID)) {
			return $this->_view_all_messages();
		} else {
			return $this->_show_for_user();
		}
	}

	//-----------------------------------------------------------------------------
	// Display popup window for adding admin message to the specified user id
	function show_popup () {
		$user_id = $this->USER_ID;
		// Get user info
		$user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($user_id));
		if (empty($user_info)) {
			return _e("No such user");
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".__CLASS__."&action=add"._add_get(),
			"user_name"		=> _display_name($user_info),
			"account_link"	=> "./?object=account&user_id=".intval($user_info["id"]),
			"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($user_info["id"])),
		);
		$body = tpl()->parse(__CLASS__."/popup_add", $replace);
		return common()->show_empty_page($body);
	}

	//-----------------------------------------------------------------------------
	// Do add message for the specified user
	function add () {
		// Check for user_id
		if (empty($this->USER_ID)) {
			return _e("User ID is required");
		}
		$user_id = $this->USER_ID;
		// Get user info
		$user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($user_id));
		if (empty($user_info)) {
			return _e("No such user");
		}
		// Do save data
		if (!empty($_POST)) {
			db()->INSERT("admin_messages", array(
				"user_id"	=> intval($user_id),
				"author_id"	=> intval($_SESSION["admin_id"]),
				"title"		=> _es($_POST["title"]),
				"text"		=> _es($_POST["text"]),
				"time"		=> time(),
			));
			// Display success form
			$replace = array(
				"user_name"				=> _display_name($user_info),
				"account_link"			=> "./?object=account&user_id=".intval($user_info["id"]),
				"view_user_msgs_link"	=> "./?object=".__CLASS__."&action=show&user_id=".$user_id,
			);
			$body = tpl()->parse(__CLASS__."/add_success", $replace);
			return common()->show_empty_page($body);
		}
	}

	//-----------------------------------------------------------------------------
	// Display link to the popup URL to send message to the given user_id
	function _popup_link ($user_id = 0) {
		if (empty($user_id)) {
			$user_id = $this->USER_ID;
		}
		return process_url("./?object=".__CLASS__."&action=show_popup&user_id=".intval($user_id));
	}

	//-----------------------------------------------------------------------------
	// Display list of sent messages for the given user
	function _show_for_user ($user_id = 0) {
		if (is_array($user_id)) {
			$user_id = $user_id["user_id"];
		}
		if (empty($user_id)) {
			$user_id = $this->USER_ID;
		}
		if (empty($user_id)) {
			return "User ID is required";
		}
		// Connect pager
		$sql = "SELECT * FROM `".db('admin_messages')."` WHERE `user_id`=".intval($user_id)." ORDER BY `time` DESC";
		list($add_sql, $pages, $total)	= common()->divide_pages($sql);
		// Get messages from the database
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"id"		=> intval($A["id"]),
				"title"		=> _prepare_html($A["title"]),
				"text"		=> _prepare_html($A["text"]),
				"add_date"	=> _format_date($A["time"], "long"),
				"user_read"	=> (int)((bool)$A["read"]),
				"view_link"	=> "./?object=".__CLASS__."&action=view&id=".$A["id"],
			);
			$items .= tpl()->parse(__CLASS__."/for_user_item", $replace2);
		}
		// Process template
		$replace = array(
			"total"			=> intval($total),
			"pages"			=> $pages,
			"items"			=> $items,
			"popup_add_link"=> $this->_popup_link(),
			"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($user_id)),
		);
		return tpl()->parse(__CLASS__."/for_user_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Display all messages for users sent from admin panel
	function _view_all_messages () {
// TODO: write templates, code is mostly done here
		// Connect pager
		$sql = "SELECT * FROM `".db('admin_messages')."`";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `time` DESC ";
		list($add_sql, $pages, $total)	= common()->divide_pages($sql);
		// Get messages from the database
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$admin_msgs[$A["id"]]		= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
			$admins_ids[$A["author_id"]]= $A["author_id"];
		}
		unset($users_ids[""]);
		unset($admins_ids[""]);
		// Get users names
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `id` IN(".implode(",",$users_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$users_infos[$A["id"]] = $A;
			}
		}
		// Get admins names
		if (!empty($admins_ids)) {
			$Q = db()->query("SELECT * FROM `".db('admin')."` WHERE `id` IN(".implode(",",$admins_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$admins_infos[$A["id"]] = $A;
			}
		}
		// Process mesaages
		foreach ((array)$admin_msgs as $A) {
			$replace2 = array(
				"id"				=> intval($A["id"]),
				"user_id"			=> intval($A["user_id"]),
				"user_name"			=> _display_name($user_infos[$A["user_id"]]),
				"user_group_id"		=> intval($user_infos[$A["user_id"]]),
				"user_group_name"	=> _prepare_html($this->_account_types[$user_infos[$A["user_id"]]["group"]]),
				"user_account_link"	=> "./?object=account&user_id=".$A["user_id"],
				"admin_id"			=> intval($A["user_id"]),
				"admin_name"		=> _prepare_html($admins_infos[$A["user_id"]]["first_name"]." ".$admins_infos[$A["user_id"]]["first_name"]),
				"admin_group_id"	=> intval($admins_infos[$A["user_id"]]["group"]),
				"title"				=> _prepare_html($A["title"]),
				"text"				=> _prepare_html($A["text"]),
				"add_date"			=> _format_date($A["time"], "long"),
				"user_read"			=> (int)((bool)$A["read"]),
				"view_link"			=> "./?object=".__CLASS__."&action=view&id=".$A["id"],
			);
			$items .= tpl()->parse(__CLASS__."/view_all_item", $replace2);
		}
		// Process template
		$replace = array(
			"total"			=> intval($total),
			"pages"			=> $pages,
			"items"			=> $items,
			"popup_add_link"=> $this->_popup_link(),
		);
		return tpl()->parse(__CLASS__."/view_all_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// View single selected message
	function view () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$msg_info = db()->query_fetch("SELECT * FROM `".db('admin_messages')."` WHERE `id`=".intval($_GET["id"]));
		}
		if (empty($msg_info)) {
			return common()->_raise_error("No such message!");
		}
		// Get user info
		$user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($msg_info["user_id"]));
		// Process template
		$replace = array(
			"title"			=> _prepare_html($msg_info["title"]),
			"text"			=> nl2br(_prepare_html($msg_info["text"])),
			"user_name"		=> _prepare_html(_display_name($user_info)),
			"add_date"		=> _format_date($msg_info["time"], "long"),
		);
		return tpl()->parse(__CLASS__."/view_msg", $replace);
	}

	//-----------------------------------------------------------------------------
	//
	function edit () {
// TODO
	}

	//-----------------------------------------------------------------------------
	//
	function delete () {
// TODO
	}

	//-----------------------------------------------------------------------------
	//
	function read () {
// TODO
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$MF = &$_SESSION[$this->_filter_name];
		foreach ((array)$MF as $k => $v) {
			$F[$k] = trim($v);
		}
// TODO
/*
		// Generate filter for the common fileds
		if ($F["id_min"]) 				$sql .= " AND `id` >= ".intval($F["id_min"])." \r\n";
		if ($F["id_max"])			 	$sql .= " AND `id` <= ".intval($F["id_max"])." \r\n";
		if (strlen($F["name"])) 		$sql .= " AND `name` LIKE '"._es($F["name"])."%' \r\n";
		if (strlen($F["nick"])) 		$sql .= " AND `nick` LIKE '"._es($F["nick"])."%' \r\n";
		if (strlen($F["email"])) 		$sql .= " AND `email` LIKE '"._es($F["email"])."%' \r\n";
		if (strlen($F["login"])) 		$sql .= " AND `login` LIKE '"._es($F["login"])."%' \r\n";
		if (strlen($F["password"])) 	$sql .= " AND `password` LIKE '"._es($F["password"])."%' \r\n";
		if ($F["account_type"])		$sql .= " AND `group` = ".intval($F["account_type"])." \r\n";
		if (strlen($F["state"]))		$sql .= " AND `state` = '".$F["state"]."' \r\n";
		if ($F["country"])	 			$sql .= " AND `country` = '".$this->_countries[$F["country"]]."' \r\n";
*/
		// Sorting here
		if ($F["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$F["sort_by"]]."` \r\n";
		if ($F["sort_by"] && strlen($F["sort_order"])) 	$sql .= " ".$F["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based filter form stored in $_SESSION[$this->_filter_name][...]
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".__CLASS__."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".__CLASS__."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse(__CLASS__."/filter", $replace);
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
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			return js_redirect("./?object=".__CLASS__._add_get());
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
