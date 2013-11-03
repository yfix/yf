<?php

/**
* Log authentification fails viewer
*/
class yf_log_admin_auth_fails_viewer {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;

	/**
	* Constructor
	*/
	function _init () {
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	*/
	function show () {

		return common()->table2("SELECT * FROM ".db('log_admin_auth_fails'))
			->text("login")
			->text("ip")
			->date("time", "Date")
			->func("reason", function($field, $params) { 
				if($field == "w") $reason = "Wrong login";
				if($field == "b") $reason = "Blocked";
				return $reason;})
			->btn('View', './?object='.$_GET["object"].'&action=view&id=%d', array('id' => 'time'))
			->render();
	}

	/**
	* Single record detailed view
	*/
	function view () {
		$_GET["id"] = floatval($_GET["id"]);
		if (!$_GET["id"]) {
			return $_SERVER["HTTP_REFERER"];
		}

		$result = db()->query_fetch("SELECT * FROM ".db('log_admin_auth_fails')." WHERE time=".$_GET["id"]);
		if (empty($result)) {
			return _e(t("Wrong ID"));
		}

		// Prepare template
		$replace = array(
			"login"			=> _prepare_html($result["login"]),
			"pswd"			=> _prepare_html($result["pswd"]),
			"ip"			=> $result["ip"],
			"date"			=> _format_date($result["time"], "long"),
			"reason"		=> $result["reason"],
			"back_url"		=> $_SERVER["HTTP_REFERER"],
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/************************* Filter methods **************************/

	/**
	* Prepare required data for filter
	*/
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";

		// Prepare boxes
		// Cur year for date boxes
		$this->cur_year = date("Y");

		$this->_boxes = array_merge((array)$this->_boxes, array(
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
			"reason"		=> 'select_box("reason",		$this->_reasons,			$selected, 0, 2, "", false)',
		));

		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"ip",
			"login",
			"reason",
		);
		// Sort fields
		$this->_reasons = array(
			"",
			"w"	=> "Wrong login",
			"b"	=> "Blocked",
		);

		// Fields in the filter
		$this->_fields_in_filter = array(
			"ip",
			"login",
			"reason",
//			"time_from",
//			"time_to",
			"sort_by",
			"sort_order",
		);
	}

	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["time_from"]) 			$sql .= " AND time >= ".$SF["time_from"]." \r\n";
		if ($SF["time_to"])				$sql .= " AND time <= ".(intval($SF["time_to"]) + 24*3600)." \r\n";
		if (strlen($SF["ip"]))			$sql .= " AND ip LIKE '"._es($SF["ip"])."%' \r\n";
		if (strlen($SF["login"]))		$sql .= " AND login LIKE '"._es($SF["login"])."%' \r\n";
		if ($SF["reason"])				$sql .= " AND reason = '"._es($SF["reason"])."' \r\n";
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY ".$this->_sort_by[$SF["sort_by"]]." \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	// Session - based filter
	function _show_filter () {
		if($_GET["action"] != "show"){
			return false;
		}
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

		$SF = $_SESSION[$this->_filter_name];
		$replace["time_from_box"]	= common()->date_box($SF["time_from"], 		($this->cur_year-1)."-".$this->cur_year, 	"_from", 	"", 	"dmy", 1, 1);
		$replace["time_to_box"]		= common()->date_box($SF["time_to"] ? $SF["time_to"] : time(),	 		($this->cur_year-1)."-".$this->cur_year, 	"_to", 		"", 	"dmy", 1, 1);

		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	// Filter save method
	function save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
		}
		$cur_year = date("Y");
		if (!$_REQUEST["year_from"] && $_REQUEST["month_from"] && $_REQUEST["day_from"]) {
			$_REQUEST["year_from"] = $cur_year;
		}

		$_SESSION[$this->_filter_name]["time_from"] = strtotime($_REQUEST["year_from"]."-".$_REQUEST["month_from"]."-".$_REQUEST["day_from"]." GMT");
		$_SESSION[$this->_filter_name]["time_to"] 	= strtotime($_REQUEST["year_to"]."-".$_REQUEST["month_to"]."-".$_REQUEST["day_to"]." GMT");

		if (!$silent) {
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

	function _hook_widget__admin_auth_fails ($params = array()) {
// TODO
	}
}
