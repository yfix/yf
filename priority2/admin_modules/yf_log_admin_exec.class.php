<?php

class yf_log_admin_exec{

	/** @var bool homes search filter on/off */
	public $USE_FILTER		= true;
	// Session array name where filter vars are stored
	public $_filter_name = "log_admin_exec_filter";


	/**
	* Framework constructor
	*/
	function _init () {
		// filter
		$this->_boxes = array(
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		 $selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	 $selected, 0, 2, "", false)',
		);
		$this->_ads_type = $GLOBALS["PAYMENT_TYPE"];
		// Sort fields
		$this->_sort_by = array(
			""	 	=> "",
			"id"	=> "id",
			"date" 	=> "date",
		);
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Fields in the filter
		$this->_fields_in_filter = array(
			"ip",
			"admin",
			"sort_by",
			"sort_order",
		);
	}


	/**
	*
	*/
	function show() {
		$sql = "SELECT * FROM ".db('log_admin_exec')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1 ". $filter_sql : " ORDER BY date ASC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$admin_info[$A["admin_id"]] = db()->query_fetch("SELECT login FROM ".db('admin')." WHERE id = '".$A["admin_id"]."' ORDER BY id ASC ");
			$items[] = array(
				"ip_country"	=> strtolower(common()->_get_country_by_ip($A["ip"])),
				"id"			=> $A["id"],
				"date"			=> _format_date($A["date"], full),
				"ip"			=> _prepare_html($A["ip"]),
				"admin"			=> _prepare_html($admin_info[$A["admin_id"]]["login"]),
				"query_string"	=> _prepare_html($A["query_string"]),
				"request_uri"	=> _prepare_html($A["request_uri"]),
				"referer"		=> _prepare_html($A["referer"]),
				"exec_time"		=> _prepare_html($A["exec_time"]),

//				"view_url"		=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
			);
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""),
			"error"				=> _e(),
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> $total,
			"prune_action"		=> "./?object=".$_GET["object"]."&action=prune",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Prune log table
	*/
	function prune () {
		if (isset($_POST["prune_days"])) {
			db()->query("DELETE FROM ".db('log_admin_exec')."".(!empty($_POST["prune_days"]) ? " WHERE date <= ".intval(time() - $_POST["prune_days"] * 86400) : ""));
			db()->query("OPTIMIZE TABLE ".db('log_admin_exec')."");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

 	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$MF = &$_SESSION[$this->_filter_name];
		foreach ((array)$MF as $k => $v) $MF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($MF["ip"])							$sql .= " AND ip = '"._es($MF["ip"])."' \r\n";
		if ($MF["admin"] && is_numeric($MF["admin"])){
					$sql .= " AND admin_id = '".intval($MF["user"])."' \r\n";
		}elseif($MF["admin"] && !is_numeric($MF["admin"])){
					$admin_id = db()->query_fetch("SELECT id FROM ".db('admin')." WHERE login = '".$MF["admin"]."' ORDER BY id ASC ");
					$sql .= " AND admin_id = '".$admin_id["id"]."' \r\n";
		}
		// Sorting here
		if ($MF["sort_by"])			 			$sql .= " ORDER BY ".$this->_sort_by[$MF["sort_by"]]." \r\n";
		if ($MF["sort_by"] && strlen($MF["sort_order"])) 	$sql .= " ".$MF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based members filter form stored in $_SESSION[$this->_filter_name][...]
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
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]."&action=show"._add_get());
		}
	}
	//-----------------------------------------------------------------------------

	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}


}