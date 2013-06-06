<?php

/**
* Display user activity
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_activity_viewer {

	/** @var bool Filter on/off */
	var $USE_FILTER		= true;

	/**
	* Framework Constructor
	*/
	function _init () {
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}

		// Timeout to refresh results
		$this->_refresh_timeout = array(
			""		=> "No refresh",
			"30"	=> "30 seconds",
			"60"	=> "1 minute",
			"180"	=> "3 minutes",
			"300"	=> "5 minutes",
			"600"	=> "10 minutes",
			"900"	=> "15 minutes",	
		);
		// Get task names 
		$task_name = main()->get_data("activity_types");
		$this->task_name[""] = "";
		foreach ((array)$task_name as $A){
			$this->task_name[$A["id"]] = str_replace("_", " ", ucfirst($A["name"]));
		}
		asort($this->task_name);
	}

	/**
	* Default method
	*/
	function show () {
		$sql = "SELECT * FROM `".db('activity_logs')."`";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `add_date` DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$A = db()->query_fetch_all($sql.$add_sql);

		// Form array with user_ids to show
		foreach ((array)$A as $V){
			$user_ids[$V["user_id"]] = $V["user_id"];
		}				
		// Get user infos
		$this->_user_info = $this->_get_users_info($user_ids);
		// Get user total activity points
		$B = db()->query_fetch_all("SELECT `user_id`, `points` FROM `".db('activity_total')."` WHERE `user_id` IN(".implode(",", $user_ids).")");
		foreach ((array)$B as $V){
			$this->total_activity[$V["user_id"]] = intval($V["points"]);
		}

		foreach ((array)$A as $V){
			$replace2 = array(
				"user"					=> _prepare_html($this->_user_info[$V["user_id"]]["nick"]),
				"user_id"				=> intval($V["user_id"]),
				"profile_link"			=> _profile_link($V["user_id"]),
				"total_activity"		=> $this->total_activity[$V["user_id"]],
				"add_points"			=> intval($V["add_points"]),
				"date"					=> _format_date($V["add_date"], "long"),
				"task_name"				=> _prepare_html($this->task_name[$V["task_id"]]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> $total, 
			"filter"				=> $this->USE_FILTER ? $this->_show_filter() : "",
			"refresh_timeout"		=> $_SESSION[$this->_filter_name]["refresh_timeout"] ? $_SESSION[$this->_filter_name]["refresh_timeout"] : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Get user info
	*/
	function _get_users_info ($user_id = array()) {
		if (!empty($user_id)) {
			$A = db()->query_fetch_all("SELECT * FROM `".db('user')."` WHERE `id` IN ('".implode("','", $user_id)."')". (MAIN_TYPE_USER ? " AND `active`='1'" : ""));
			foreach ((array)$A as $V){
				$_user_info[$V["id"]] = $V;
			}
		}
		return $_user_info;
	}

	/**
	* Prepare required data for filter
	*/
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"refresh_timeout"	=> 'select_box("refresh_timeout",	$this->_refresh_timeout,	$selected, 0, 2, "", false)',
			"task_id"			=> 'select_box("task_id",			$this->task_name,			$selected, 0, 2, "", false)',
			"sort_by"			=> 'select_box("sort_by",			$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"		=> 'select_box("sort_order",		$this->_sort_orders,		$selected, 0, 2, "", false)',
		));
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"user_id",
			"add_date",
			"task_id",
			"add_points",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_id",
			"task_id",
			"refresh_timeout",
			"sort_by",
			"sort_order",	
		);
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["user_id"])		 							$sql .= " AND `user_id` = ".intval($SF["user_id"])." \r\n";
		if ($SF["task_id"])									$sql .= " AND `task_id`=".intval($SF["task_id"])." \r\n";
		// Sorting here
		if ($SF["sort_by"])			 						$sql .= " ORDER BY `".$this->_sort_by[$SF["sort_by"]]."` \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	/**
	* Session - based filter
	*/
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

	/**
	* Filter save method
	*/
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

	/**
	* Clear filter
	*/
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

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = _ucfirst(t("Activity viewer"));
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
}
