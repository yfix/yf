<?php

// Blogs management module
class yf_manage_blogs {

	/** @var int Text preview cutoff (set to 0 to disable) */
	public $TEXT_PREVIEW_LENGTH	= 0;
	/** @var bool Filter on/off */
	public $USE_FILTER				= true;

	
	// Constructor
	function yf_manage_blogs() {
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

	
	// Default method (display blog posts)
	function show () {
		// Connect pager
		$sql = "SELECT SQL_CALC_FOUND_ROWS id FROM ".db('blog_posts')." ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY add_date DESC ";
		// Add current page limit
		$per_page = conf('admin_per_page');
		$first_record = intval(($_GET["page"] ? $_GET["page"] - 1 : 0) * $per_page);
		if ($first_record < 0) {
			$first_record = 0;
		}
		$sql .= " LIMIT ".intval($first_record).",".intval($per_page);
		// Get users ids
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) $blog_posts_ids[$A["id"]]	= $A["id"];
		// Prepare other info
		if (!empty($blog_posts_ids)) {
			// Prepare pages
			list($num_posts) = db()->query_fetch("SELECT FOUND_ROWS() AS `0`", false);
			list(, $pages, ) = common()->divide_pages("", "./?object=".$_GET["object"], "", $per_page, $num_posts);
			// Get posts infos
			$Q = db()->query("SELECT * FROM ".db('blog_posts')." WHERE id IN(".implode(",", $blog_posts_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$blog_posts_array[$A["id"]] = $A;
				$users_ids[$A["user_id"]]	= $A["user_id"];
			}
		}
		// Get real user names
		if (isset($users_ids[""])) {
			unset($users_ids[""]);
		}
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM ".db('user')." WHERE id IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_names[$A["id"]] = _display_name($A);
		}
		// Process posts
		foreach ((array) $blog_posts_ids as $cur_post_id) {
			$post_info = $blog_posts_array[$cur_post_id];
			if (!empty($this->TEXT_PREVIEW_LENGTH)) {
				$post_info["text"] = substr($post_info["text"], 0, $this->TEXT_PREVIEW_LENGTH);
			}
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"post_id"		=> $post_info["id"],
				"user_id"		=> $post_info["user_id"],
				"title"			=> _prepare_html($post_info["title"]),
				"text"			=> nl2br(_prepare_html($post_info["text"])),
				"add_date"		=> _format_date($post_info["add_date"], "long"),
				"user_name"		=> _prepare_html($post_info["user_name"]), // As posted in blog
				"user_nick"		=> _prepare_html($users_names[$post_info["user_id"]]),
				"user_id"		=> intval($post_info["user_id"]),
				"member_url"	=> "./?object=account&user_id=".$post_info["user_id"],
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$post_info["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$post_info["id"],
				"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($post_info["user_id"])),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_delete",
			"total"		=> intval($num_posts),
			"items"		=> $items,
			"pages"		=> $pages,
			"filter"	=> $this->USE_FILTER ? $this->_show_filter() : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	
	// Edit record
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id"));
		}
		// Try to get record info
		$post_info = db()->query_fetch("SELECT * FROM ".db('blog_posts')." WHERE id=".intval($_GET["id"]));
		if (empty($post_info)) {
			return _e(t("No such post"));
		}
		// Try to get given user info
		$user_info = db()->query_fetch("SELECT id,name,nick,photo_verified FROM ".db('user')." WHERE id=".intval($post_info["user_id"]));
		if (empty($user_info["id"])) {
			return _e(t("No such user!"));
		}
		// Check posted data and save
		if (count($_POST) > 0) {
			if (empty($_POST["title"])) {
				_re(t("Title required"));
			}
			if (empty($_POST["text"])) {
				_re(t("Text required"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Check text fields
//				$_POST["title"]	= _filter_text($_POST["title"]);
//				$_POST["text"]	= _filter_text($_POST["text"]);
				// Do update record
				db()->UPDATE("blog_posts", array(
					"title" 		=> _es($_POST["title"]),
					"text" 			=> _es($_POST["text"]),
				), "id=".intval($post_info["id"]));
				// Return user back
				return js_redirect($RETURN_PATH, false);
			}
		}
		// Show form
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".intval($_GET["id"]),
			"error_message"		=> $error_message,
			"user_id"			=> intval(main()->USER_ID),
			"user_name"			=> _prepare_html(_display_name($user_info)),
			"user_avatar"		=> _show_avatar($post_info["user_id"], $user_info, 1, 1),
			"user_profile_link"	=> _profile_link($post_info["user_id"]),
			"user_email_link"	=> _email_link($post_info["user_id"]),
			"title"				=> _prepare_html($post_info["title"]),
			"text"				=> _prepare_html($post_info["text"]),
			"back_url"			=> "./?object=".$_GET["object"],
			"ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($post_info["user_id"])),
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Try to get given user info
		if (!empty($_GET["id"])) {
			$post_info = db()->query_fetch("SELECT * FROM ".db('blog_posts')." WHERE id=".intval($_GET["id"]));
		}
		// Do delete record
		if (!empty($post_info)) {
			db()->query("DELETE FROM ".db('blog_posts')." WHERE id=".intval($_GET["id"])." LIMIT 1");
			// Remove activity points
			common()->_remove_activity_points($post_info["user_id"], "blog_post", $_GET["id"]);
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	
	// Mass delete records
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
			db()->query("DELETE FROM ".db('blog_posts')." WHERE id IN(".implode(",",$ids_to_delete).")");
		}
		// Return user back
//		return js_redirect("./?object=".$_GET["object"]);
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"active"		=> 'select_box("active",		$this->_active_statuses,$selected, 0, 2, "", false)',
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
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"id",
			"user_id",
			"add_date",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"date_min",
			"date_max",
			"nick",
			"user_id",
			"title",
			"text",
			"account_type",
			"sort_by",
			"sort_order",
			"plblog_only",
		);
	}

	
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["date_min"]) 			$sql .= " AND add_date >= ".strtotime($SF["date_min"])." \r\n";
		if ($SF["date_max"])			$sql .= " AND add_date <= ".strtotime($SF["date_max"])." \r\n";
		if ($SF["user_id"])			 	$sql .= " AND user_id = ".intval($SF["user_id"])." \r\n";
		if (strlen($SF["title"]))		$sql .= " AND title LIKE '"._es($SF["title"])."%' \r\n";
		if (strlen($SF["text"]))		$sql .= " AND text LIKE '"._es($SF["text"])."%' \r\n";
		if (in_array($SF["active"], array(1,-1))) {
		 	$sql .= " AND active = '".intval($SF["active"] == 1 ? 1 : 0)."' \r\n";
		}
		if (strlen($SF["nick"]) || strlen($SF["account_type"]) || $SF["plblog_only"]) {
			if (strlen($SF["nick"])) 	$users_sql .= " AND nick LIKE '"._es($SF["nick"])."%' \r\n";
			if ($SF["account_type"])	$users_sql .= " AND `group` = ".intval($SF["account_type"])." \r\n";
			if ($SF["plblog_only"])		$users_sql .= " AND old_id != 0 \r\n";
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

	
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	
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
		$pheader = t("Manage blogs");
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

	function _hook_widget__blogs_stats ($params = array()) {
// TODO
	}

	function _hook_widget__blogs_top_authors ($params = array()) {
// TODO
	}

	function _hook_widget__blogs_most_popular ($params = array()) {
// TODO
	}

	function _hook_widget__blogs_latest ($params = array()) {
// TODO
	}
}
