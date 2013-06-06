<?php

/**
* Catalog editor
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_catalog_editor {

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_catalog_editor () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		// Array of select boxes to process
		$this->_boxes = array(
			"active"		=> 'radio_box("active",			$this->_statuses,			$selected, false, 2, "", false)',
			"featured"		=> 'radio_box("featured",		$this->_statuses,			$selected, false, 2, "", false)',
			"parent_id"		=> 'select_box("parent_id",		$this->_items_for_parent,	$selected, false, 2, "", false)',
			"item_order"	=> 'select_box("item_order",	$this->_item_orders,		$selected, false, 2, "", false)',
			"groups"		=> 'multi_select("groups",		$this->_groups,				$selected, false, 2, " size=7 ", false)',
			"node_type"		=> 'select_box("node_type",		$this->_node_types,			$selected, false, 2, "", false)',
		);
		// Array of statuses
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);

        // Get node types
		$Q = db()->query("SELECT * FROM `".db('catalog_node_types')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) $this->_node_types[$A['id']] = $A['name'];

		// Get user groups
		$this->_user_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT `id`,`name` FROM `".db('user_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) $this->_user_groups[$A['id']] = $A['name'];
		// Get admin groups
		$this->_admin_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT `id`,`name` FROM `".db('admin_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) $this->_admin_groups[$A['id']] = $A['name'];
	}

	/**
	* Display catalogs blocks
	* 
	* @access	public
	* @return	string
	*/
	function show() {
		// Get catalogs
		$Q = db()->query("SELECT * FROM `".db('catalogs')."` ORDER BY `type` DESC, `active` ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),
				"type"			=> _prepare_html($A["type"]),
				"stpl_name"		=> _prepare_html($A["stpl_name"]),
				"method_name"	=> _prepare_html($A["method_name"]),
				"active"		=> intval($A["active"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"items_link"	=> "./?object=".$_GET["object"]."&action=show_items&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Add new catalog block
	* 
	* @access	public
	* @return	string
	*/
	function add() {
		// Do save data
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				// Save data
				db()->INSERT("catalogs", array(
					"name"			=> _es($_POST["name"]),
					"desc"			=> _es($_POST["desc"]),
					"stpl_name"		=> _es($_POST["stpl_name"]),
					"method_name"	=> _es($_POST["method_name"]),
					"active"		=> (int)((bool)$_POST["active"]),
					"type"			=> _es($_POST["type"]),
				));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	cache()->refresh("catalogs");
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		foreach ((array)$cat_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Show form
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"name"			=> _prepare_html($DATA["name"]),
			"desc"			=> _prepare_html($DATA["desc"]),
			"type"			=> _prepare_html($DATA["type"]),
			"stpl_name"		=> _prepare_html($DATA["stpl_name"]),
			"method_name"	=> _prepare_html($DATA["method_name"]),
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"]."&action=show",
			"for_edit"		=> 0,
		);
		return tpl()->parse($_GET["object"]."/edit_catalog", $replace);
	}

	/**
	* Edit catalog block
	* 
	* @access	public
	* @param	int		$_GET["id"]
	* @return	string
	*/
	function edit() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current catalog info
		$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($cat_info["id"])) {
			return _e(t("No such catalog!"));
		}
		// Do save data
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				// Save data
				db()->UPDATE("catalogs", array(
					"name"			=> _es($_POST["name"]),
					"desc"			=> _es($_POST["desc"]),
					"stpl_name"		=> _es($_POST["stpl_name"]),
					"method_name"	=> _es($_POST["method_name"]),
					"active"		=> (int)((bool)$_POST["active"]),
				), "`id`=".intval($_GET["id"]));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	cache()->refresh("cats_blocks");
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		foreach ((array)$cat_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Show form
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> _prepare_html($DATA["name"]),
			"desc"			=> _prepare_html($DATA["desc"]),
			"type"			=> _prepare_html($DATA["type"]),
			"stpl_name"		=> _prepare_html($DATA["stpl_name"]),
			"method_name"	=> _prepare_html($DATA["method_name"]),
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"]."&action=show",
			"for_edit"		=> 1,
		);
		return tpl()->parse($_GET["object"]."/edit_catalog", $replace);
	}

	/**
	* Delete catalog block and all sub items
	* 
	* @access	public
	* @param	int		$_GET["id"]
	* @return	string
	*/
	function delete() {
		$_GET["id"] = intval($_GET["id"]);
		// Get current catalog info
		if (!empty($_GET["id"])) {
			$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete catalog and its items
		if (!empty($cat_info["id"])) {
			db()->query("DELETE FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM `".db('catalog_items')."` WHERE `cat_id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("cats_blocks");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Change catalog block activity
	* 
	* @access	public
	* @param	int		$_GET["id"]
	* @return	string
	*/
	function activate() {
		// Try to find such catalog in db
		if (!empty($_GET["id"])) {
			$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do change activity status
		if (!empty($cat_info)) {
			db()->UPDATE("catalogs", array("active" => (int)!$cat_info["active"]), "`id`=".intval($cat_info["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("cats_blocks");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($cat_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Display catalog items for the given block
	*/
	function show_items() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current catalog info
		$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($cat_info)) {
			return _e(t("No such catalog!"));
		}
		// Get items for the current catalog
		$cat_items = $this->_recursive_get_cat_items($_GET["id"]);
		// Slice items according to the current page
		$total = count($cat_items);
		$PER_PAGE = !empty($this->ITEMS_PER_PAGE) ? $this->ITEMS_PER_PAGE : conf('admin_per_page');
		list(,$pages,) = common()->divide_pages(null, null, null, $PER_PAGE, $total);
		// Get a slice from the whole array
		if (count($cat_items) > $PER_PAGE) {
			$cat_items = array_slice($cat_items, (empty($_GET["page"]) ? 0 : intval($_GET["page"]) - 1) * $PER_PAGE, $PER_PAGE);
		}
		// Switch between arrays for admin or user
		if ($cat_info["type"] == "admin") {
			$this->_groups	= &$this->_admin_groups;
			$this->_methods = &$this->_admin_methods;
		} else {
			$this->_groups	= &$this->_user_groups;
			$this->_methods = &$this->_user_methods;
		}
		// Process items
		foreach ((array)$cat_items as $A) {
			if (empty($A)) {
				continue;
			}
			// Propose short URL for catalog
			if (empty($A["url"]) && $this->PROPOSE_SHORT_URL) {
				$A["url"] = common()->_propose_url_from_name($A["name"]);
			}
			// Prepare data
			$groups = array();
			foreach (explode(",",$A["user_groups"]) as $k => $v) {
				if (empty($this->_groups[$v])) continue;
				$groups[] = $this->_groups[$v];
			}
			$groups = implode("<br />",$groups);
			// Process template
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"item_id"		=> intval($A["id"]),
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),
				"url"			=> _prepare_html($A["url"]),
				"item_type"		=> $this->_item_types[$A["type_id"]],
				"groups"		=> $groups,
				"active"		=> intval($A["active"]),
				"order"			=> intval($A["order"]),
				"level_pad"		=> $A["level"] * 20,
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_item&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_item&id=".$A["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate_item&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/catalog_items_item", $replace2);
		}
		// Process template
		$replace = array(
			"save_form_action"	=> "./?object=".$_GET["object"]."&action=save_items&id=".$_GET["id"],
			"items"				=> $items,
			"pages"				=> $pages,
			"num_items"			=> intval($total),
			"catalog_name"		=> _prepare_html($cat_info["name"]),
			"add_item_link"		=> "./?object=".$_GET["object"]."&action=add_item&id=".$_GET["id"],
			"back_link"			=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/catalog_items_main", $replace);
	}

	/**
	* Save catalog items (several at one time)
	*/
	function save_items() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current catalog info
		$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($cat_info)) {
			return _e(t("No such catalog!"));
		}
		// Get items for the current catalog
		$cat_items = $this->_recursive_get_cat_items($_GET["id"]);
		// Process items
		foreach ((array)$cat_items as $A) {
			// Skip wrong ids
			if (!isset($_POST["name"][$A["id"]])) continue;
			// Save data
			db()->UPDATE("catalog_items", array(
				"name"		=> _es($_POST["name"][$A["id"]]),
				"url"		=> _es($_POST["url"][$A["id"]]),
				"order"		=> intval($_POST["order"][$A["id"]]),
			), "`id`=".intval($A["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("catalog_items");
		// Return user back
		js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$_GET["id"]);
	}

	/**
	* Add new catalog item
	*/
	function add_item() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current catalog info
		$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($cat_info["id"])) {
			return _e(t("No such catalog!"));
		}
		// Save data
		if (isset($_POST["go"])) {
			// Cleanup user_groups
			if (is_array($_POST["groups"]))	{
				$_POST["groups"] = implode(",",$_POST["groups"]);
			}
			$_POST["groups"]	= str_replace(array(" ","\t","\r","\n"), "", $_POST["groups"]);
			// Do insert record
			db()->INSERT("catalog_items", array(
				"cat_id"		=> intval($_GET["id"]),
				"type_id"		=> intval($_POST["type_id"]),
				"parent_id"		=> intval($_POST["parent_id"]),
				"name"			=> _es($_POST["name"]),
				"desc"			=> _es($_POST["desc"]),
				"url"			=> _es($_POST["url"]),
				"icon"			=> _es($_POST["icon"]),
				"user_groups"	=> _es($_POST["groups"]),
				"other_info"	=> _es($_POST["other_info"]),
				"featured"		=> intval($_POST["featured"]),
				"order"			=> intval($_POST["item_order"]),
				"active"		=> intval($_POST["active"]),
			));
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("catalog_items");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$cat_info["id"]);
		}
		// Get items for the current catalog
		$this->_items_for_parent[0] = "-- TOP --";
		foreach ((array)$this->_recursive_get_cat_items($_GET["id"]) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) continue;
			$this->_items_for_parent[$cur_item_id] = str_repeat("&nbsp;", $cur_item_info["level"] * 6)." &#9492; ".$cur_item_info["name"];
		}
		// Switch between arrays for admin or user
		if ($cat_info["type"] == "admin") {
			$this->_groups	= &$this->_admin_groups;
			$this->_methods = &$this->_admin_methods;
		} else {
			$this->_groups	= &$this->_user_groups;
			$this->_methods = &$this->_user_methods;
		}
		// Show template contents
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"catalog_name"		=> _prepare_html($cat_info["name"]),
			"name"				=> _prepare_html($DATA["name"]),
			"desc"				=> nl2br(_prepare_html($DATA["desc"])),
			"other_info"		=> _prepare_html($DATA["other_info"]),
			"url"				=> _prepare_html($DATA["url"]),
			"icon"				=> _prepare_html($DATA["icon"]),
			"order"				=> intval($DATA["order"]),
			"type_id_box"		=> $this->_box("type_id",	""),
			"parent_id_box"		=> $this->_box("parent_id", ""),
			"groups_box"		=> $this->_box("groups",	array(""=>"-- ALL --")),
			"methods_box"		=> $this->_box("methods",	""),
			"active_box"		=> $this->_box("active", 	$DATA["active"]),
			"featured_box"		=> $this->_box("featured",	""),
			"node_type_box"		=> $this->_box("node_type",	""),
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_items&id=".intval($cat_info["id"]),
			"for_edit"			=> 0,
			"edit_modules_link"	=> "./?object=".$cat_info["type"]."_modules",
			"edit_groups_link"	=> "./?object=".$cat_info["type"]."_groups",
		);
		return tpl()->parse($_GET["object"]."/edit_item_form", $replace);
	}

	/**
	* Edit catalog item
	*/
	function edit_item() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current item info
		$item_info = db()->query_fetch("SELECT * FROM `".db('catalog_items')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($item_info["id"])) {
			return _e(t("No such catalog item!"));
		}
		// Get current catalog info
		$cat_info = db()->query_fetch("SELECT * FROM `".db('catalogs')."` WHERE `id`=".intval($item_info["cat_id"]));
		if (empty($cat_info["id"])) {
			return _e(t("No such catalog!"));
		}
		// Save data
		if (isset($_POST["go"])) {
			// Cleanup user_groups
			if (is_array($_POST["groups"]))	{
				$_POST["groups"] = implode(",",$_POST["groups"]);
			}
			$_POST["groups"]	= str_replace(array(" ","\t","\r","\n"), "", $_POST["groups"]);
			// Do insert record
			db()->UPDATE("catalog_items", array(
				"parent_id"		=> intval($_POST["parent_id"]),
				"name"			=> _es($_POST["name"]),
				"desc"			=> _es($_POST["desc"]),
				"url"			=> _es($_POST["url"]),
				"icon"			=> _es($_POST["icon"]),
				"user_groups"	=> _es($_POST["groups"]),
				"other_info"	=> _es($_POST["other_info"]),
				"featured"		=> intval($_POST["featured"]),
				"type_id"		=> intval($_POST["type_id"]),
				"order"			=> intval($_POST["item_order"]),
				"active"		=> intval($_POST["active"]),
			), "`id`=".intval($item_info["id"]));
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("catalog_items");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$cat_info["id"]);
		}
		// Get items for the current catalog
		$this->_items_for_parent[0] = "-- TOP --";
		foreach ((array)$this->_recursive_get_cat_items($cat_info["id"], $_GET["id"]) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) continue;
			$this->_items_for_parent[$cur_item_id] = str_repeat("&nbsp; &nbsp; &nbsp; ", $cur_item_info["level"])." &#9492; &nbsp; ".$cur_item_info["name"];
		}
		// Prepare user groups
		$item_info["user_groups"]	= explode(",",str_replace(array(" ","\t","\r","\n"), "", $item_info["user_groups"]));
		foreach ((array)$item_info["user_groups"] as $v) $tmp[$v] = $v;
		$item_info["user_groups"] = $tmp;
		// Switch between arrays for admin or user
		if ($cat_info["type"] == "admin") {
			$this->_groups	= &$this->_admin_groups;
			$this->_methods = &$this->_admin_methods;
		} else {
			$this->_groups	= &$this->_user_groups;
			$this->_methods = &$this->_user_methods;
		}
		// Propose short URL for catalog
		if (empty($item_info["url"]) && $this->PROPOSE_SHORT_URL) {
			$item_info["url"] = common()->_propose_url_from_name($item_info["name"]);
		}
		// Process template
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"catalog_name"		=> _prepare_html($cat_info["name"]),
			"name"				=> _prepare_html($item_info["name"]),
			"desc"				=> nl2br(_prepare_html($item_info["desc"])),
			"other_info"		=> _prepare_html($item_info["other_info"]),
			"url"				=> _prepare_html($item_info["url"]),
			"icon"				=> _prepare_html($item_info["icon"]),
			"order"				=> intval($item_info["order"]),
			"type_id_box"		=> $this->_box("type_id",		$item_info["type_id"]),
			"parent_id_box"		=> $this->_box("parent_id", 	$item_info["parent_id"]),
			"groups_box"		=> $this->_box("groups",		$item_info["user_groups"]),
			"methods_box"		=> $this->_box("methods",		""),
			"active_box"		=> $this->_box("active", 		$item_info["active"]),
			"featured_box"		=> $this->_box("featured",		$item_info["featured"]),
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_items&id=".intval($cat_info["id"]),
			"for_edit"			=> 1,
			"edit_modules_link"	=> "./?object=".$cat_info["type"]."_modules",
			"edit_groups_link"	=> "./?object=".$cat_info["type"]."_groups",
		);
		return tpl()->parse($_GET["object"]."/edit_item_form", $replace);
	}

	/**
	* Change catalog item activity
	*/
	function activate_item() {
		// Try to find such catalog item in db
		if (!empty($_GET["id"])) {
			$item_info = db()->query_fetch("SELECT * FROM `".db('catalog_items')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do change activity status
		if (!empty($item_info)) {
			db()->UPDATE("catalog_items", array("active" => (int)!$item_info["active"]), "`id`=".intval($item_info["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("catalog_items");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($item_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$item_info["cat_id"]);
		}
	}

	/**
	* Delete item
	*/
	function delete_item() {
		$_GET["id"] = intval($_GET["id"]);
		// Try to find such catalog item in db
		if (!empty($_GET["id"])) {
			$item_info = db()->query_fetch("SELECT * FROM `".db('catalog_items')."` WHERE `id`=".intval($_GET["id"]));
		}
// FIXME: add recursive deletion of all children
		// Do delete catalog and its items
		if (!empty($item_info)) {
			db()->query("DELETE FROM `".db('catalog_items')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("catalog_items");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$item_info["cat_id"]);
		}
	}

	/**
	* Import catalog items
	*/
	function import() {
// TODO
	}

	/**
	* Export catalog items
	*/
	function export() {
// TODO
	}

	/**
	* Get catalog items ordered array (recursively)
	*/
	function _recursive_get_cat_items($cat_id = 0, $skip_item_id = 0, $parent_id = 0, $level = 0) {
		if (!isset($this->_catalog_items_from_db)) {
			$Q = db()->query("SELECT * FROM `".db('catalog_items')."` WHERE `cat_id`=".intval($cat_id)." ORDER BY `order` ASC");
			while ($A = db()->fetch_assoc($Q)) $this->_catalog_items_from_db[$A["id"]] = $A;
		}
		if (empty($this->_catalog_items_from_db)) {
			return "";
		}
		$items_ids		= array();
		$items_array	= array();
		// Get items from the current level
		foreach ((array)$this->_catalog_items_from_db as $item_info) {
			// Skip items from other parents
			if ($item_info["parent_id"] != $parent_id) {
				continue;
			}
			// Skip item if needed (and all its children)
			if ($skip_item_id == $item_info["id"]) {
				continue;
			}
			// Add item to the result array
			$items_array[$item_info["id"]] = $item_info;
			$items_array[$item_info["id"]]["level"] = $level;
			// Try to find sub items
			$tmp_array = $this->_recursive_get_cat_items($cat_id, $skip_item_id, $item_info["id"], $level + 1);
			foreach ((array)$tmp_array as $sub_item_info) {
				if ($sub_item_info["id"] == $item_info["id"]) {
					continue;
				}
				$items_array[$sub_item_info["id"]] = $sub_item_info;
			}
		}
		return $items_array;
	}

	/**
	* Process custom box
	*/
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
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Catalog editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "All catalogs list",
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
