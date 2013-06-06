<?php

/**
* Users "log in" info analyser
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_log_auth_view {

	/** @var bool Filter on/off */
	var $USE_FILTER		= true;

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_log_auth_view () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		$this->USER_ID = $_GET['user_id'];
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	*/
	function show () {
		// Calling function to divide records per pages
		$sql = "SELECT * FROM `".db('log_auth')."` ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `date` DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get records
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$records[] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users infos
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `id` IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_infos[$A["id"]] = $A;
		}
		// Process data
		foreach ((array)$records as $A) {
			$cur_user_info = $users_infos[$A["user_id"]];
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"user_id"		=> intval($cur_user_info["id"]),
				"user_name"		=> _prepare_html(_display_name($cur_user_info)),
				"user_nick"		=> _prepare_html($cur_user_info["nick"]),
				"user_avatar"	=> _show_avatar($A["user_id"], $cur_user_info, 1),
				"group_name"	=> t($this->_account_types[$user_info["group"]]),
				"member_url"	=> "./?object=account&action=show&user_id=".$cur_user_info["id"],
				"user_email"	=> _prepare_html($cur_user_info["email"]),
				"log_login"		=> _prepare_html($A["login"]),
				"log_ip"		=> _prepare_html($A["ip"]),
				"log_ua"		=> _prepare_html($A["user_agent"]),
				"log_referer"	=> _prepare_html($A["referer"]),
				"log_date"		=> _format_date($A["date"], "long"),
				"for_user_link"	=> "./?object=".$_GET["object"]."&action=show_for_user&id=".$A["user_id"],
				"for_ip_link"	=> "./?object=".$_GET["object"]."&action=show_for_ip&id=".$A["ip"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"total"				=> intval($total),
			"items"				=> $items,
			"pages"				=> $pages,
			"filter"			=> $this->USE_FILTER ? $this->_show_filter() : "",
			"prune_action"		=> "./?object=".$_GET["object"]."&action=prune",
			"same_ips_action"	=> "./?object=".$_GET["object"]."&action=show_same_ips",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Prune log table
	*/
	function prune () {
		if (isset($_POST["prune_days"])) {
			db()->query("DELETE FROM `".db('log_auth')."`".(!empty($_POST["prune_days"]) ? " WHERE `date` <= ".intval(time() - $_POST["prune_days"] * 86400) : ""));
			db()->query("OPTIMIZE TABLE `".db('log_auth')."`");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Show log logins for selected user
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
	* Show log logins for selected IP address
	*/
	function show_for_ip () {
		$_GET["id"] = substr(preg_replace("/[^0-9\.]/", "", trim($_GET["id"])), 0, 15);
		// Do save filter
		$_REQUEST["ip"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show();
	}

	/**
	* Show same ips for selected users
	*/
	function show_same_ips () {
		$_GET["id"] = preg_replace("/[^0-9,]/", "", trim($_REQUEST["id"]));
		// Prepare users ids to process
		$users_ids = array();
		foreach ((array)explode(",", $_GET["id"]) as $tmp) {
			$_id = intval($tmp);
			if (empty($_id)) {
				continue;
			}
			$users_ids[$_id] = $_id;
		}
		// Check array
		if (empty($users_ids)) {
			return "Please specify user ids to analyse";
		}
		// Get same ips
		$Q = db()->query(
			"SELECT COUNT(DISTINCT(`user_id`)) AS `unique_accounts`, 
				COUNT(*) AS `num_logins_from_this_ip`, 
				`ip` 
			FROM `".db('log_auth')."` 
			WHERE `user_id` IN (".implode(",",$users_ids).") 
			GROUP BY `ip` 
			HAVING `unique_accounts` > 1
			ORDER BY `unique_accounts` DESC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$items[] = array(
				"unique_accounts"	=> intval($A["unique_accounts"]),
				"num_logins"		=> intval($A["num_logins_from_this_ip"]),
				"ip"				=> _prepare_html($A["ip"]),
				"ip_link"			=> "./?object=".$_GET["object"]."&action=show_for_ip&id=".$A["ip"],
			);
		}
		// Prepare template
		$replace = array(
			"items"		=> $items,
			"users_ids"	=> implode(",", $users_ids),
		);
		return tpl()->parse($_GET["object"]."/same_ips", $replace);
	}

	//-----------------------------------------------------------------------------
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
			"user_agent",
			"referer",
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
		if ($SF["date_min"]) 			$sql .= " AND `date` >= ".strtotime($SF["date_min"])." \r\n";
		if ($SF["date_max"])			$sql .= " AND `date` <= ".strtotime($SF["date_max"])." \r\n";
		if ($SF["user_id"])			 	$sql .= " AND `user_id` = ".intval($SF["user_id"])." \r\n";
		if (strlen($SF["ip"]))			$sql .= " AND `ip` LIKE '"._es($SF["ip"])."%' \r\n";
		if (strlen($SF["user_agent"]))	$sql .= " AND `user_agent` LIKE '"._es($SF["user_agent"])."%' \r\n";
		if (strlen($SF["referer"]))		$sql .= " AND `referer` LIKE '"._es($SF["referer"])."%' \r\n";
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
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
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
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		return array(
			"header"	=> t("Log auth"),
			"subheader"	=> "",
		);
	}

}
