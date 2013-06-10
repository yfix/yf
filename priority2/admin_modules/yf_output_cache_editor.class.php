<?php

/**
* Manage output cache pages
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_output_cache_editor {

	/** @var int */
	public $VIEW_PER_PAGE	= 50;
	/** @var bool */
	public $USE_FILTER		= true;

	/**
	* Constructor
	*/
	function yf_output_cache_editor() {
		// Init dir class
		$this->DIR_OBJ	= main()->init_class("dir", "classes/");
		// Init output cache class
		$this->OC_OBJ	= main()->init_class("output_cache", "classes/");
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;
	}

	/**
	* Default function
	*/
	function show () {
		$cached_total_size	= 0;
		$cached_total_files	= 0;
		$include_pattern	= "/".preg_quote($this->OC_OBJ->CACHE_FILE_EXT, "/")."\$/";
/*
		// Cache folders are separate for every site
		if ($this->OC_OBJ->USE_SITES_DIRS) {
			// Get stats about sites local cache dirs
			foreach ((array)$this->_sites_info->info as $site_id => $SITE_INFO) {
				$site_cache_dir = $SITE_INFO["REAL_PATH"].$this->OC_OBJ->OUTPUT_CACHE_DIR;
				$dir_size		= $this->DIR_OBJ->dirsize($site_cache_dir, $include_pattern);
				$num_files		= $dir_size ? $this->DIR_OBJ->count_files($site_cache_dir, $include_pattern) : 0;
				$cached_total_size	+= $dir_size;
				$cached_total_files += $num_files;
				$dir_items[] = array(
					"num"		=> ++$i,
					"site_id"	=> intval($site_id),
					"bg_class"	=> !($i % 2) ? "bg1" : "bg2",
					"site_name"	=> $SITE_INFO["name"],
					"size"		=> common()->format_file_size($dir_size),
					"num_files"	=> intval($num_files),
					"path"		=> $site_cache_dir,
					"path_link"	=> "./?object=file_manager&dir_name=".urlencode($site_cache_dir),
					"clean_link"=> "./?object=".$_GET["object"]."&action=clean_site&id=".$site_id,
				);
			}
		} else {
			// TODO
		}
*/
		// Prepare custom TTLs
		foreach ((array)$this->OC_OBJ->CUSTOM_CACHE_TTLS as $_pattern => $_ttl) {
			$custom_ttls[] = array(
				"pattern"	=> _prepare_html($_pattern, 0),
				"ttl"		=> intval($_ttl),
			);
		}
		// Prepare template
		$replace = array(
			"dir_items" 		=> $dir_items,
			"custom_ttls" 		=> $custom_ttls,
			"caching_enabled"	=> intval((bool)$this->OC_OBJ->OUTPUT_CACHING),
			"cache_ttl"			=> intval($this->OC_OBJ->OUTPUT_CACHE_TTL),
			"cache_ttl_days"	=> intval($this->OC_OBJ->OUTPUT_CACHE_TTL / 86400),
			"cached_total_size"	=> common()->format_file_size($cached_total_size),
			"cached_total_files"=> intval($cached_total_files),
			"change_status_link"=> "./?object=".$_GET["object"]."&action=change_status",
			"avail_pages_link"	=> "./?object=".$_GET["object"]."&action=view",
			"clean_all_link"	=> "./?object=".$_GET["object"]."&action=clean_all",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* View available pages list
	*/
	function view () {
		$sql = "SELECT * FROM `".db('cache_info')."`"/*." GROUP BY `query_string`"*/;
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : ""/*" ORDER BY `date` DESC "*/;
		list($add_sql, $pages, $total) = common()->divide_pages(str_replace("SELECT *", "SELECT `id`", $sql), $path, null, $this->VIEW_PER_PAGE);
		// Process users
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$site_info	= $this->_sites_info->info[$A["site_id"]];
			$cache_dir	= "";
			if (!empty($site_info)) {
				$cache_dir	= ($this->OC_OBJ->USE_SITES_DIRS ? $site_info["REAL_PATH"] : INCLUDE_PATH). $this->OC_OBJ->OUTPUT_CACHE_DIR;
				if ($this->OC_OBJ->USE_SUB_DIRS_FOR_CACHE) {
					$cache_dir .= $A["hash"][0]."/".$A["hash"][1]."/".$A["hash"][2]."/";
				}
			}
			$cache_file = !empty($cache_dir) ? $cache_dir. $A["hash"]. $this->OC_OBJ->CACHE_FILE_EXT : "";
			$is_cached = !empty($cache_file) && file_exists($cache_file) ? 1 : 0;
			// Prepare template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"object"		=> $A["object"],
				"object"		=> $A["object"],
				"action"		=> $A["action"],
				"query_string"	=> _prepare_html($A["query_string"]),
				"hash"			=> $A["hash"],
				"site_id"		=> $A["site_id"],
				"site_name"		=> _prepare_html($site_info["name"]),
				"group"			=> $A["group_id"] == 1 ? "Guest" : "Member",
				"clean_link"	=> "./?object=".$_GET["object"]."&action=clean&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"page_link"		=> $site_info["WEB_PATH"]."?".$A["query_string"],
				"view_link"		=> $is_cached ? "./?object=file_manager&action=edit_item&f_=".basename($cache_file)."&dir_name=".urlencode(dirname($cache_file)) : "",
				"is_cached"		=> intval((bool)$is_cached),
				"cache_size"	=> $is_cached ? intval(filesize($cache_file)) : "",
			);
			$items .= tpl()->parse($_GET["object"]."/view_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"total"			=> intval($total),
			"pages"			=> $pages,
			"main_link"		=> "./?object=".$_GET["object"]."&action=show",
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
			"form_action"	=> "./?object=".$_GET["object"]."&action=clean",
		);
		return tpl()->parse($_GET["object"]."/view_main", $replace);
	}

	/**
	* Change caching status (on/off)
	*/
	function change_status () {
		// Get current setting
		$A = db()->query_fetch("SELECT * FROM `".db('settings')."` WHERE `item`='output_caching'");
		// Do update with new one
		if (!empty($A["id"])) {
			db()->UPDATE("settings", array("value" => $A["value"] ? 0 : 1), "`id`=".intval($A["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("settings");
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Delete selected page
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM `".db('cache_info')."` WHERE `id`=".intval($_GET["id"]));
			$_POST["ids"][$_GET["id"]] = 1;
			// Do clean cache entries
			$this->clean();
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Clean selected pages
	*/
	function clean () {
		// Prepare ids to clean
		$cache_ids = array();
		foreach ((array)$_POST["ids"] as $_cur_id => $_selected) {
			$_cur_id = intval($_cur_id);
			if (empty($_cur_id)) {
				continue;
			}
			$cache_ids[$_cur_id] = $_cur_id;
		}
		// Get selected records from db
		if (!empty($cache_ids)) {
			$Q = db()->query("SELECT * FROM `".db('cache_info')."` WHERE `id` IN(".implode(",", $cache_ids).")");
			while ($A = db()->fetch_assoc($Q)) $cache_infos[$A["id"]] = $A;
		}
		// Process records
		foreach ((array)$cache_infos as $_cache_id => $A) {
			$site_info	= $this->_sites_info->info[$A["site_id"]];
			$cache_dir	= "";
			if (!empty($site_info)) {
				$cache_dir	= ($this->OC_OBJ->USE_SITES_DIRS ? $site_info["REAL_PATH"] : INCLUDE_PATH). $this->OC_OBJ->OUTPUT_CACHE_DIR;
				if ($this->OC_OBJ->USE_SUB_DIRS_FOR_CACHE) {
					$cache_dir .= $A["hash"][0]."/".$A["hash"][1]."/".$A["hash"][2]."/";
				}
			}
			$cache_file = !empty($cache_dir) ? $cache_dir. $A["hash"]. $this->OC_OBJ->CACHE_FILE_EXT : "";
			if (empty($cache_file) || !file_exists($cache_file)) {
				continue;
			}
			// Do delete cache file from HDD
			unlink($cache_file);
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Clean all
	*/
	function clean_all () {
		// Cache folders are separate for every site
		if ($this->OC_OBJ->USE_SITES_DIRS) {
			// Get stats about sites local cache dirs
			foreach ((array)$this->_sites_info->info as $site_id => $SITE_INFO) {
				// Prepare cache base dir
				$cache_dir = $SITE_INFO["REAL_PATH"]. $this->OC_OBJ->OUTPUT_CACHE_DIR;
				// Do delete cache files
				$this->DIR_OBJ->delete_files($cache_dir, "/.*".preg_quote($this->OC_OBJ->CACHE_FILE_EXT, "/")."\$/");
			}
		} else {
			// Prepare cache base dir
			$cache_dir = INCLUDE_PATH. $this->OC_OBJ->OUTPUT_CACHE_DIR;
			// Do delete cache files
			$this->DIR_OBJ->delete_files($cache_dir, "/.*".preg_quote($this->OC_OBJ->CACHE_FILE_EXT, "/")."\$/");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Clean site
	*/
	function clean_site () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$site_info = $this->_sites_info->info[$_GET["id"]];
		}
		// Check if site found
		if (!empty($site_info) && $this->OC_OBJ->USE_SITES_DIRS) {
			// Prepare cache base dir
			$cache_dir = $site_info["REAL_PATH"]. $this->OC_OBJ->OUTPUT_CACHE_DIR;
			// Do delete cache files
			$this->DIR_OBJ->delete_files($cache_dir, "/.*".preg_quote($this->OC_OBJ->CACHE_FILE_EXT, "/")."\$/");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"object"		=> 'select_box("object",		$this->_objects,			$selected, 0, 2, "", false)',
			"action"		=> 'select_box("action",		$this->_actions,			$selected, 0, 2, "", false)',
			"group"			=> 'select_box("group",			$this->_groups,				$selected, 0, 2, "", false)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,		$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
		));
		// Get user account type
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		// Prepare groups
		$this->_groups = array(
			""	=> t("-- All --"),
			1	=> t("Guest"),
			2	=> t("Member"),
		);
		// Get unique objects
		$this->_objects[""]	= t("-- All --");
		$Q = db()->query("SELECT `object` FROM `".db('cache_info')."` WHERE `object` != '' GROUP BY `object` ORDER BY `object` ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_objects[strtolower($A["object"])]	= strtolower($A["object"]);
		}
		// Get unique actions
		$this->_actions[""]	= t("-- All --");
		$Q = db()->query("SELECT `action` FROM `".db('cache_info')."` WHERE `action` != '' GROUP BY `action` ORDER BY `action` ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_actions[strtolower($A["action"])]	= strtolower($A["action"]);
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"object",
			"action",
			"query_string",
			"site_id",
			"group_id",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"object",
			"action",
			"query_string",
			"account_type",
			"group",
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
		if ($SF["group"])			 		$sql .= " AND `group_id` = ".intval($SF["group"])." \r\n";
		if ($SF["object"])			 		$sql .= " AND `object` = '"._es($SF["object"])."' \r\n";
		if ($SF["action"])				 	$sql .= " AND `action` = '"._es($SF["action"])."' \r\n";
		if (strlen($SF["query_string"]))	$sql .= " AND `query_string` LIKE '%"._es($SF["query_string"])."%' \r\n";
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$SF["sort_by"]]."` \r\n";
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
		if (FEATURED_COUNTRY_SELECT && !empty($_REQUEST["country"]) && substr($_REQUEST["country"], 0, 2) == "f_") {
			$_REQUEST["country"] = substr($_REQUEST["country"], 2);
		}
		foreach ((array)$this->_fields_in_filter as $name) {
			$_SESSION[$this->_filter_name][$name] = $_POST[$name];
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
			return js_redirect($_SERVER["HTTP_REFERER"]);
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
				"name"	=> "List of pages to cache",
				"url"	=> "./?object=".$_GET["object"]."&action=view",
			),
			array(
				"name"	=> "Clean all cached pages ",
				"url"	=> "./?object=".$_GET["object"]."&action=clean_all",
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
		$pheader = t("Output cache editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"view"					=> "",
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
