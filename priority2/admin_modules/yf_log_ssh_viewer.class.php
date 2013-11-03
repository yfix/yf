<?php

/**
* Log ssh viewer
*/
class yf_log_ssh_viewer {

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
		// Do save filter if needed
		if (!empty($_GET["server"])) {
			$_POST["server"] = $_GET["server"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}

		// Prepare pager
		$sql = "SELECT * FROM ".db('log_ssh_action')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY microtime DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql. $add_sql);

		foreach ((array)$records as $result) {
			$server_ids[] 	= $result["server_id"];
			$user_ids[]		= $result["user_id"];
		}		
		$user_ids	= array_unique((array)$user_ids);

		// Find user_infos and server infos
		$user_info 		= user($user_ids, array("nick"));

		foreach ((array)$records as $result) {
			$replace2 = array(
				"server"		=> $result["server_id"],
				"date"			=> _format_date($result["microtime"], "long"),
				"ip"			=> $result["ip"],
				"comment"		=> _prepare_html($result["comment"]),
				"get_object"	=> $result["get_object"],
				"get_action"	=> $result["get_action"],
				"action"		=> _prepare_html($result["action"]),

				"details_link"	=> "./?object=".$_GET["object"]."&action=view&id=".floatval($result["microtime"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}

		// Prepare template
		$replace = array(
			"total"			=> $total,
			"pages"			=> $pages,
			"items"			=> $items,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Single record detailed view
	*/
	function view () {
		$_GET["id"] = floatval($_GET["id"]);
		if (!$_GET["id"]) {
			return $_SERVER["HTTP_REFERER"];
		}

		$result = db()->query_fetch("SELECT * FROM ".db('log_ssh_action')." WHERE microtime=".$_GET["id"]);
		if (empty($result)) {
			return _e(t("Wrong ID"));
		}

		// Prepare template
		$replace = array(
			"server"		=> $result["server_id"],
			"date"			=> _format_date($result["microtime"], "long"),
			"ip"			=> $result["ip"],
			"comment"		=> _prepare_html($result["comment"]),
			"get_object"	=> $result["get_object"],
			"get_action"	=> $result["get_action"],
			"action"		=> _prepare_html($result["action"]),
			"init_type"		=> _prepare_html($result["init_type"]),
			"user_id"		=> $result["user_id"],
			"user_group"	=> $result["user_group"],

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

		// Process calendar
		if ($this->USE_JS_CALENDAR == true) {
			$CALENDAR_OBJ = main()->init_class("js_calendar", "classes/");
		}
		// Prepare boxes
		$Q = db()->query("SELECT * FROM ".db('servers')." WHERE active = '1' ORDER BY name ASC");
		while($A = db()->fetch_assoc($Q)) {
			$this->_servers[$A["base_ip"]] = $A["name"];
		}
 

		$this->_boxes = array_merge((array)$this->_boxes, array(
			"server"		=> 'select_box("server",		$this->_servers,			$selected, 1, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
		));

		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"server",
			"ip",
			"get_object",
			"get_action",
			"action",
			"comment",
		);

		// Fields in the filter
		$this->_fields_in_filter = array(
			"time_from",
			"time_to",
			"server",
			"ip",
			"action",
			"comment",
			"sort_by",
			"sort_order",
		);
	}

	
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) {
			$SF[$k] = trim($v);
		}
		// Generate filter for the common fileds
		if ($SF["time_from"])			$sql .= " AND microtime >= ".intval(strtotime($SF["time_from"]))." \r\n";
		if ($SF["time_to"])				$sql .= " AND microtime <= ".(intval(strtotime($SF["time_to"])) + 24*3600)." \r\n";
		if (strlen($SF["action"]))		$sql .= " AND action LIKE '"._es($SF["action"])."%' \r\n";
		if (strlen($SF["server"]))		$sql .= " AND server_id LIKE '"._es($SF["server"])."%' \r\n";
		if (strlen($SF["ip"]))			$sql .= " AND ip LIKE '"._es($SF["ip"])."%' \r\n";
		if (strlen($SF["comment"]))		$sql .= " AND comment LIKE '"._es($SF["comment"])."%' \r\n";

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
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}

		// For date
		$_SESSION[$this->_filter_name]["time_from"] = $_POST["time_from"];
		$_SESSION[$this->_filter_name]["time_to"] 	= $_POST["time_to"];

		if (!$silent) {
			if (!empty($_POST["go_home"])) {
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
			if (!empty($_POST["go_home"])) {
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

	function _hook_widget__ssh_actions_log ($params = array()) {
// TODO
	}
}
