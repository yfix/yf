<?php

/**
* Display core errors
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_log_core_errors_viewer {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var array @conf_skip Standard error types */
	public $_error_levels = array(
		1		=> "E_ERROR",
		2		=> "E_WARNING",
		4		=> "E_PARSE",
		8		=> "E_NOTICE",
		16		=> "E_CORE_ERROR",
		32		=> "E_CORE_WARNING",
		64		=> "E_COMPILE_ERROR",
		128		=> "E_COMPILE_WARNING",
		256		=> "E_USER_ERROR",
		512		=> "E_USER_WARNING",
		1024	=> "E_USER_NOTICE",
		2047	=> "E_ALL",
		2048	=> "E_STRICT",
		4096	=> "E_RECOVERABLE_ERROR",
	);
	/** @var array CSS classes for different error levels */
	public $_css_classes = array(
		1		=> "log_e_error",
		2		=> "log_e_warn",
		8		=> "log_e_notice",
		256		=> "log_e_error",
		512		=> "log_e_warn",
		1024	=> "log_e_notice",
	);

	/**
	* Constructor
	*/
	function _init () {
		main()->USER_ID = $_GET['user_id'];
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
		// Try to get info about sites vars
		$this->_sites_info = _class("sites_info");
	}

	/**
	* Default method
	*/
	function show () {
		// Prepare pager
		$sql = "SELECT * FROM ".db('log_core_errors')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get records from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$records[] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users infos
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT id,group,login,nick,email,photo_verified FROM ".db('user')." WHERE id IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_infos[$A["id"]] = $A;
		}
		// Process data
		foreach ((array)$records as $A) {
			if (!empty($A["user_id"])) {
				$cur_user_info = $users_infos[$A["user_id"]];
			}
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"error_level"	=> intval($A["error_level"]),
				"level_name"	=> _prepare_html($this->_error_levels[$A["error_level"]]),
				"message"		=> nl2br(_prepare_html($A["error_text"])),
				"date"			=> _format_date($A["date"], "long"),
				"td_class"		=> $this->_css_classes[$A["error_level"]],
				"user_id"		=> intval($cur_user_info["id"]),
				"user_name"		=> _prepare_html($cur_user_info["name"]),
				"user_nick"		=> _prepare_html($cur_user_info["nick"]),
				"user_avatar"	=> _show_avatar($A["user_id"], $cur_user_info, 1),
				"group_name"	=> t($this->_account_types[$user_info["group"]]),
				"member_url"	=> "./?object=account&action=show&user_id=".$cur_user_info["id"],
				"user_email"	=> _prepare_html($cur_user_info["email"]),
				"details_link"	=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"by_user_link"	=> $A["user_id"] ? "./?object=".$_GET["object"]."&action=show_for_user&id=".$A["user_id"] : "",
				"by_ip_link"	=> "./?object=".$_GET["object"]."&action=show_for_ip&id=".$A["ip"],
				"query_string"	=> _prepare_html($A["query_string"]),
				"request_uri"	=> _prepare_html($A["request_uri"]),
				"log_ip"		=> _prepare_html($A["ip"]),
				"log_browser"	=> _prepare_html($A["user_agent"]),
				"log_referer"	=> _prepare_html($A["referer"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Prepare teplate
		$replace = array(
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> intval($total),
			"prune_action"			=> "./?object=".$_GET["object"]."&action=prune",
			"form_action"			=> "./?object=".$_GET["object"]."&action=multi_delete",
			"top"					=> "./?object=".$_GET["object"]."&action=top_of_errors",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* View error log record details
	*/
	function view () {
		$_GET["id"] = intval($_GET["id"]);
		// Get record
		if (!empty($_GET["id"])) {
			$log_info = db()->query_fetch("SELECT * FROM ".db('log_core_errors')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($log_info)) {
			return "No such record!";
		}
		$A = &$log_info;
		// Get user info
		if (!$A["is_admin"] && !empty($A["user_id"])) {
			$cur_user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE id =".intval($A["user_id"]));
		}
		// Process template
		$replace = array(
			"record_id"			=> intval($A["id"]),
			"error_level"		=> intval($A["error_level"]),
			"level_name"		=> _prepare_html($this->_error_levels[$A["error_level"]]),
			"message"			=> nl2br(_prepare_html(trim($A["error_text"]))),
			"date"				=> _format_date($A["date"], "long"),
			"td_class"			=> $this->_css_classes[$A["error_level"]],
			"user_id"			=> intval($cur_user_info["id"]),
			"user_name"			=> _prepare_html($cur_user_info["name"]),
			"user_nick"			=> _prepare_html($cur_user_info["nick"]),
			"user_avatar"		=> _show_avatar($A["user_id"], $cur_user_info, 1),
			"user_group"		=> $A["user_group"] > 1 ? t($this->_account_types[$A["user_group"]]) : "GUEST",
			"member_url"		=> "./?object=account&action=show&user_id=".$cur_user_info["id"],
			"user_email"		=> _prepare_html($cur_user_info["email"]),
			"details_link"		=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
			"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
			"query_string"		=> _prepare_html($A["query_string"]),
			"request_uri"		=> _prepare_html($A["request_uri"]),
			"log_ip"			=> _prepare_html($A["ip"]),
			"log_browser"		=> _prepare_html($A["user_agent"]),
			"log_referer"		=> _prepare_html($A["referer"]),
			"back_link"			=> "./?object=".$_GET["object"],
			"source_file"		=> _prepare_html($A["source_file"], 0),
			"source_line"		=> _prepare_html($A["source_line"]),
			"edit_source_link"	=> file_exists($A["source_file"]) ? "./?object=file_manager&action=edit_item&f_=".basename($A["source_file"])."&dir_name=".urlencode(dirname($A["source_file"])) : "",
			"site_id"			=> intval($A["site_id"]),
			"site_name"			=> !empty($A["site_id"]) ? _prepare_html($this->_sites_info->info[$A["site_id"]]["name"]) : "",
			"site_link"			=> $this->_sites_info->info[$A["site_id"]]["WEB_PATH"],
			"section_name"		=> $A["is_admin"] ? "ADMIN" : "USER",
			"env_data"			=> !empty($A["env_data"]) ? printr(@unserialize($A["env_data"]), 1) : "",
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* Delete record
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		db()->query("DELETE FROM ".db('log_core_errors')." WHERE id=".intval($_GET["id"]));
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Multi delete records
	*/
	function multi_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM ".db('log_core_errors')." WHERE id IN(".implode(",",$ids_to_delete).")");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Prune log table
	*/
	function prune () {
		if (isset($_POST["prune_days"])) {
			db()->query("DELETE FROM ".db('log_core_errors')."".(!empty($_POST["prune_days"]) ? " WHERE date <= ".intval(time() - $_POST["prune_days"] * 86400) : ""));
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Clean log table
	*/
	function clean () {
		// Do delete record
		db()->query("TRUNCATE TABLE ".db('log_core_errors')."");
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Show data for selected user
	*/
	function show_for_user () {
		$_GET["id"] = intval($_GET["id"]);
		// Do save filter
		$_REQUEST["user_id"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show();
	}

	/**
	* Show data for selected IP address
	*/
	function show_for_ip () {
		// Do save filter
		$_REQUEST["ip"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show();
	}

	// Delete filtered records
	function delete_all_filtered () {
		// Prepare query for deleting
		$p_replace = "/(ORDER BY)(.)+/ims";
		if ($_POST["confirm"]){	
			$sql = "DELETE FROM ".db('log_core_errors')." WHERE 1=1 ".$this->_create_filter_sql();
			$sql = preg_replace($p_replace, "", $sql);
			$result = db()->query($sql);
			$this->clear_filter(1);
			return js_redirect("./?object=".$_GET["object"]._add_get());
		} else {
			$sql = "SELECT COUNT(*) AS `0` FROM ".db('log_core_errors')." WHERE 1=1 ".$this->_create_filter_sql();
			$sql = preg_replace($p_replace, "", $sql);
			list ($num_records) = db()->query_fetch($sql);
			$replace = array(
				"confirmed"		=> "./?object=".$_GET["object"]."&action=delete_all_filtered"._add_get(),
				"num_records"	=> $num_records,
				"cancel_del"	=> "./?object=".$_GET["object"]._add_get(),
			);			
			return tpl()->parse($_GET["object"]."/confirm_delete", $replace);
		}
	}	

	// Forming top of errors
	function top_of_errors () {
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;

		$sql = "SELECT id, error_level, error_text, COUNT(error_text) AS num FROM ".db('log_core_errors')." GROUP BY error_text ORDER BY num DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql.$add_sql);

		// Process data
		foreach ((array)$records as $A) {
			// Prepare template
			$replace2 = array(
				"level_name"	=> _prepare_html($this->_error_levels[$A["error_level"]]),
				"message"		=> _prepare_html(trim($A["error_text"])),
				"num"			=> $A["num"],
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item_top", $replace2);
		}
		$replace =array (
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_filter&go_home=1",
		);
		return tpl()->parse($_GET["object"]."/main_top", $replace);
	}

	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,		$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
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
			"user_id",
			"date",
			"ip",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_id",
			"account_type",
			"ip",
			"error_text",
			"user_agent",
			"referer",
			"sort_by",
			"sort_order",
		);
	}

	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["date_min"]) 			$sql .= " AND date >= ".strtotime($SF["date_min"])." \r\n";
		if ($SF["date_max"])			$sql .= " AND date <= ".strtotime($SF["date_max"])." \r\n";
		if ($SF["user_id"])			 	$sql .= " AND user_id = ".intval($SF["user_id"])." \r\n";
		if (strlen($SF["ip"]))			$sql .= " AND ip LIKE '"._es($SF["ip"])."%' \r\n";
		if (strlen($SF["user_agent"]))	$sql .= " AND user_agent LIKE '"._es($SF["user_agent"])."%' \r\n";
		if (strlen($SF["referer"]))		$sql .= " AND referer LIKE '"._es($SF["referer"])."%' \r\n";
		if (strlen($SF["error_text"]))	$sql .= " AND error_text LIKE '%"._es($SF["error_text"])."%' \r\n";
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
			"delete_sel"	=> "./?object=".$_GET["object"]."&action=delete_all_filtered"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = _prepare_html($_SESSION[$this->_filter_name][$name]);
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
		if (FEATURED_COUNTRY_SELECT && !empty($_REQUEST["country"]) && substr($_REQUEST["country"], 0, 2) == "f_") {
			$_REQUEST["country"] = substr($_REQUEST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) {
				$_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
			}
		}
		if (!$silent) {
			if (!empty($_POST["delete_by_filter"])) {
				$sql = "DELETE FROM ".db('log_core_errors')." WHERE 1=1 ".$this->_create_filter_sql();
				$sql = preg_replace("/(ORDER BY)(.)+/ims", "", $sql);
				$result = db()->query($sql);
				$this->clear_filter(1);
				return js_redirect("./?object=".$_GET["object"]."&action=top_of_errors");
			}
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect("./?object=".$_GET["object"]._add_get());
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
				"name"	=> "Top of errors",			  
				"url"	=> "./?object=".$_GET["object"]."&action=top_of_errors",
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
		$pheader = t("Core errors viewer");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
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

	function _hook_widget__core_errors_log ($params = array()) {
// TODO
	}
}
