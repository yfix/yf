<?php

/**
* Log authentification fails viewer
*/
class yf_log_webshell_actions_viewer {

	/** @var bool Filter on/off */
	var $USE_FILTER		= true;

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

		// Prepare pager
		$sql = "SELECT * FROM `".db('log_webshell_action')."`";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `microtime` DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql. $add_sql);

		foreach ((array)$records as $result) {
			$server_ids[] 	= $result["server_id"];
			$user_ids[]		= $result["user_id"];
		}		
		$server_ids	= array_unique((array)$server_ids);
		$user_ids	= array_unique((array)$user_ids);

		// Find user_infos and server infos
		$user_info 		= user($user_ids, array("nick"));
		$server_info	= _server_info($server_ids);

		foreach ((array)$records as $result) {
			$replace2 = array(
				"server"		=> $server_info[$result["server_id"]]["name"],
				"user"			=> $user_info[$result["user_id"]]["nick"],
				"date"			=> _format_date($result["microtime"], "long"),
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
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
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
		));

		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"server_id",
			"user_id",
			"action",
		);

		// Fields in the filter
		$this->_fields_in_filter = array(
			"server",
			"user",
			"action",
			"sort_by",
			"sort_order",
		);
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) {
			$SF[$k] = trim($v);
		}
		// Generate filter for the common fileds
		if ($SF["time_from"])			$sql .= " AND `microtime` >= ".$SF["time_from"]." \r\n";
		if ($SF["time_to"])				$sql .= " AND `microtime` <= ".(intval($SF["time_to"]) + 24*3600)." \r\n";
		if (!empty($SF["user_id"])) {
			$sql .= " AND `user_id` IN('".implode("','", explode(",", $SF["user_id"]))."') \r\n";
		}
		if (!empty($SF["server_id"])) {
			$sql .= " AND `server_id` IN('".implode("','", explode(",", $SF["server_id"]))."') \r\n";
		}
		if (strlen($SF["action"]))		$sql .= " AND `action` LIKE '"._es($SF["action"])."%' \r\n";

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

	//-----------------------------------------------------------------------------
	// Filter save method
	function save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}

		$_SESSION[$this->_filter_name]["user"] = $_POST["user"];
		$_SESSION[$this->_filter_name]["server"] = $_POST["server"];

		$_SESSION[$this->_filter_name]["user_id"] = "";
		$_SESSION[$this->_filter_name]["server_id"] = "";
		if ($_POST["user"]) {
			if (is_numeric($_POST["user"])){
				$_SESSION[$this->_filter_name]["user_id"] = intval($_POST["user"]);
			} else {
				$Q = db()->query("
					SELECT `id` 
					FROM `".db('user')."` 
					WHERE `nick` LIKE '".$_POST["user"]."%' 
						AND `id` IN(SELECT DISTINCT `user_id` FROM `".db('log_webshell_action')."`) 
					");
				while ($A = db()->fetch_assoc($Q)) {
					$_tmp_users[intval($A["id"])] = intval($A["id"]);
				}
				$_SESSION[$this->_filter_name]["user_id"] = implode(",", $_tmp_users);
			}
		}


		if ($_POST["server"]) {
			if (is_numeric($_POST["server"])){
				$_SESSION[$this->_filter_name]["server_id"] = intval($_POST["server"]);
			} else {
				$Q = db()->query("
					SELECT `id` 
					FROM `".db('servers')."` 
					WHERE `name` LIKE '".$_POST["server"]."%' 
						AND `id` IN(SELECT DISTINCT `server_id` FROM `".db('log_webshell_action')."`) 
				");
				while ($A = db()->fetch_assoc($Q)) {
					$_tmp_servers[intval($A["id"])] = intval($A["id"]);
				}
				$_SESSION[$this->_filter_name]["server_id"] = implode(",", $_tmp_servers);
			}
		}

		// For date boxes
		$cur_year = date("Y");
		if (!$_POST["year_from"] && $_POST["month_from"] && $_POST["day_from"]) {
			$_POST["year_from"] = $cur_year;
		}
		$_SESSION[$this->_filter_name]["time_from"] = strtotime($_POST["year_from"]."-".$_POST["month_from"]."-".$_POST["day_from"]." GMT");
		$_SESSION[$this->_filter_name]["time_to"] 	= strtotime($_POST["year_to"]."-".$_POST["month_to"]."-".$_POST["day_to"]." GMT");

		if (!$silent) {
			if (!empty($_POST["go_home"])) {
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
			if (!empty($_POST["go_home"])) {
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

}
