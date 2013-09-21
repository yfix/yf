<?php

/**
* Display online users
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_online_users_manager {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var int */
	public $COLUMNS		= 4;

	/**
	*/
	function __construct () {
		// Get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
		// Get user groups
		$A = db()->query_fetch_all("SELECT * FROM ".db('user_groups')."");
		foreach ((array)$A as $V) {
			$this->_user_group[$V["id"]] = _prepare_html($V["name"]);			
		}
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
		// Timeout to refresh results
		$this->_refresh_timeout = array(
			"30"	=> "30 seconds",
			"60"	=> "1 minute",
			"180"	=> "3 minutes",
			"300"	=> "5 minutes",
			"600"	=> "10 minutes",
			"900"	=> "15 minutes",	
		);
	}

	/**
	* Default method
	* 
	* @access
	* @param
	* @return
	*/
	function show () {
		list($guests_online) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM ".db('online')." WHERE user_id=0");
		$sql = "SELECT * FROM ".db('online')."" ;
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? "  WHERE user_id>0". $filter_sql : " WHERE user_id>0 ORDER BY time DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$A = db()->query_fetch_all($sql.$add_sql);
		$user_ids = array();
		// Start counter of columns
		$i = 1;

		foreach ((array)$A as $V){
			$user_ids[$V["user_id"]] = $V["user_id"];
			$this->_user_info = $this->_get_users_info($user_ids);
			parse_str($V["query_string"], $q_string);
			if ($V["site_id"]) {
				$location = $this->_sites_info->info[$V["site_id"]]["WEB_PATH"]."?".$V["query_string"];
				if ($q_string){
				$location_text = $q_string["object"];		
					if($q_string["action"]) {
						$location_text .= "->".$q_string["action"]; 
					}
				}
			}
			$replace2 = array(
				"user_nick"		=> _prepare_html($this->_user_info[$V["user_id"]]["nick"]),
				"user_avatar" 	=> _show_avatar($V["user_id"], $this->_user_info),	
				"profile_link"	=> _profile_link($V["user_id"]),
				"user_group"	=> $this->_user_group[$V["user_group"]],
				"location"		=> $location,
				"location_text"	=> $location_text,
				"user_agent"	=> _prepare_html($V["user_agent"]),
				"ip"			=> _prepare_html($V["ip"]),
				"time"			=> _format_date($V["time"], "long"),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
			if ($i < $this->COLUMNS){
				$items .= tpl()->parse($_GET["object"]."/item_terminator");
				$i++;
			} elseif ($i >= $this->COLUMNS) {
				$items .= tpl()->parse($_GET["object"]."/row_terminator");
				$i = 1;
			} 
		}
		if ($i != 1 && $i < $this->COLUMNS) {
			while (($this->COLUMNS - $i) > 0) {
				$items .= tpl()->parse($_GET["object"]."/item_terminator");
				$i++;
			}
		}
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> $total, 
			"guests"			=> $guests_online,
			"show_guests_link"	=> "./?object=".$_GET["object"]."&action=guests_online",
			"refresh_timeout"	=> $_SESSION[$this->_filter_name]["refresh_timeout"] ? $_SESSION[$this->_filter_name]["refresh_timeout"] : ""
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Shows online guests
	* 
	* @access
	* @param
	* @return
	*/
	function guests_online () { 
		$sql = "SELECT * FROM ".db('online')." WHERE user_id=0";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$A = db()->query_fetch_all($sql.$add_sql);
		foreach ((array)$A as $V) {
			parse_str($V["query_string"], $q_string);
			if ($V["site_id"]) {
				$location = $this->_sites_info->info[$V["site_id"]]["WEB_PATH"]."?".$V["query_string"];
				if ($q_string){
				$location_text = $q_string["object"];		
					if($q_string["action"]) {
						$location_text .= "->".$q_string["action"]; 
					}
				}
			}
			if (!$q_string["object"]){
				$location_text = "Home page";
				if ($q_string["task"]){
					$location_text = $q_string["task"];
				}			
			}
			$replace2 = array(
				"session_id"	=> $V["id"],
				"location"		=> $location,
				"location_text"	=> $location_text,
				"user_agent"	=> _prepare_html($V["user_agent"]),
				"ip"			=> _prepare_html($V["ip"]),
				"time"			=> _format_date($V["time"], "long"),
			);
			$items .= tpl()->parse($_GET["object"]."/guest_item", $replace2);
		}
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> $total, 
		);
		return tpl()->parse($_GET["object"]."/guest_main", $replace);
	}

	// Get user info
	function _get_users_info ($user_id = array()) {
		if (!empty($user_id)) {
			$A = db()->query_fetch_all("SELECT * FROM ".db('user')." WHERE id IN ('".implode("','", $user_id)."')". (MAIN_TYPE_USER ? " AND active='1'" : ""));
			foreach ((array)$A as $V){
				$_user_info[$V["id"]] = $V;
			}
		}
		return $_user_info;
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"user_group"		=> 'select_box("user_group",		$this->_user_group,			$selected, 1, 2, "", false)',
			"refresh_timeout"	=> 'select_box("refresh_timeout",	$this->_refresh_timeout,	$selected, 1, 2, "", false)',
			"sort_by"			=> 'select_box("sort_by",			$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"		=> 'select_box("sort_order",		$this->_sort_orders,		$selected, 0, 2, "", false)',
		));
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"user_group",
			"query_string",
			"time",
			"ip",
			"nick",
			"useragent",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_group",
			"nick",
			"refresh_timeout",
			"show_w_avatar",
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
		if ($SF["user_group"] && intval($SF["user_group"]) != 1)	$sql .= " AND user_group = ".intval($SF["user_group"])." \r\n";
		if ($SF["user_group"] && intval($SF["user_group"]) == 1)	$sql .= " AND user_group <= ".intval($SF["user_group"])." \r\n";
		if (strlen($SF["nick"])) 									$sql .= " AND user_id IN(SELECT id FROM ".db('user')." WHERE nick LIKE '%"._es($SF["nick"])."%') \r\n";
		if (intval($SF["show_w_avatar"]) == 1)						$sql .= " AND user_id IN(SELECT id FROM ".db('user')." WHERE has_avatar=1)  \r\n";
		// Sorting here
		if ($SF["sort_by"])			 						$sql .= " ORDER BY ".$this->_sort_by[$SF["sort_by"]]." \r\n";
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
			$replace[$name] = _prepare_html($_SESSION[$this->_filter_name][$name]);
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
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
		}
		if (!$silent) {
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	//-----------------------------------------------------------------------------
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
		$pheader = t("Online users manager");
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
