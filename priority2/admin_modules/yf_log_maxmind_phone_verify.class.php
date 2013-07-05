<?php

/**
* Display Maxmind phone verify service usage log
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_log_maxmind_phone_verify {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var */
	public $_check_type_full = array(
		"i"	=> "Identification",
		"v"	=> "Verification",
	);

	/**
	* Constructor
	*/
	function _init () {
		$this->_phone_type_full = main()->get_data("maxmind_phone_id_code");
		$this->_phone_type_select["-1"] = "";		
		foreach ((array)$this->_phone_type_full as $k=>$v){
			$this->_phone_type_select[$k] = $v["type"];
		}
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	*/
	function show () {
		$sql = "SELECT id, check_type, owner_id, phone_num, phone_type, verify_code, date, success 
				FROM ".db('log_maxmind_phone_verify')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$A = db()->query_fetch_all($sql.$add_sql);
		if ($A){
			foreach ((array)$A as $v){
				if (empty($v["owner_id"])) {
					continue;
				}
				$users_ids[$v["owner_id"]] = $v["owner_id"];
			}
			if (!empty($users_ids)) {
				$B = db()->query_fetch_all("SELECT id, nick FROM ".db('user')." WHERE id IN (".implode(",", $users_ids).")");
				foreach ((array)$B as $v){
					$users_array[$v["id"]] = _prepare_html($v["nick"]);
				}
			}

			foreach ((array)$A as $v){
				$replace2 = array(
					"bg_class"			=> $i++ % 2 ? "bg1" : "bg2",
					"check_type"	 	=> _prepare_html($v["check_type"]),
					"check_type_full" 	=> $this->_check_type_full[$v["check_type"]],
					"user_nick"			=> $users_array[$v["owner_id"]],
					"owner_id"			=> $v["owner_id"],
					"profile_link"		=> _profile_link($v["owner_id"]),
					"phone_num"			=> $v["phone_num"],
					"phone_type"		=> $this->_phone_type_full[$v["phone_type"]]["type"],
					"verify_code"		=> $v["verify_code"],
					"date"				=> _format_date($v["date"], "long"),
					"success"			=> $v["success"],
					"view_link"			=> "./?object=".$_GET["object"]."&action=view&id=".$v["id"],
					"force_verify_link"	=> "./?object=".$_GET["object"]."&action=force_verify&id=".$v["id"],
				);
				$items .= tpl()->parse($_GET["object"]."/item", $replace2); 
			}
		}
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
			"identify_link"	=> "./?object=".$_GET["object"]."&action=force_identify",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Shows full record info
	* 
	* @access
	* @param
	* @return
	*/
	function view () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$A = db()->query_fetch("SELECT * FROM ".db('log_maxmind_phone_verify')." WHERE id='".$_GET["id"]."'");
		$B = db()->query_fetch("SELECT nick FROM ".db('user')." WHERE id=".$A["owner_id"]);
		$replace = array(
			"record_id"		=> $A["id"],
			"user_id"		=> $A["owner_id"],
			"user_nick"		=> $B["nick"],
			"check_type"	=> _prepare_html($A["check_type"]),
			"check_type_full"=> $this->_check_type_full[$A["check_type"]],
			"phone_num"		=> _prepare_html($A["phone_num"]),
			"phone_type"	=> $this->_phone_type_full[$A["phone_type"]]["type"],
			"verify_code"	=> $A["verify_code"],
			"ref_id"		=> $A["ref_id"],
			"success"		=> $A["success"],
			"date"			=> _format_date($A["date"], "long"),
			"process_time"	=> $A["process_time"],
			"error_text"	=> _prepare_html($A["error_text"]),
			"site_id"		=> $A["site_id"],
			"id_user"		=> $A["owner_id"],
			"user_group"	=> $A["user_group"],
			"is_admin"		=> $A["is_admin"],
			"log_ip"		=> _prepare_html($A["ip"]),
			"query_string"	=> _prepare_html($A["query_string"]),
			"log_browser"	=> _prepare_html($A["user_agent"]),
			"log_referer"	=> _prepare_html($A["referer"]),
			"request_uri"	=> _prepare_html($A["request_uri"]),
			"object"		=> $A["object"],
			"action"		=> $A["action"],
			"server_answer"	=> _prepare_html($A["server_answer"]),
			"force_verify_link"	=> "./?object=".$_GET["object"]."&action=force_verify&id=".$_GET["id"],
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* Do force phone identify
	*/
	function force_identify () {
		if ($_SESSION["admin_group"] != 1) {
			return _e("Tih action only for admin with group=1");
		}
		$OBJ = main()->init_class("maxmind_phone_verify", "classes/");
		if (!empty($_POST)) {
			if (!empty($_POST["user2"])) {
				$_POST["user"] = $_POST["user2"];
			}
			$_POST["user"] = intval($_POST["user"]);

			$A = user($_POST["user"], array("phone", "country"));

			$check_result = $OBJ->_send_request($A["phone"], $_POST["user"], $A["country"]);

			return $OBJ->_action_log. "<br />".($OBJ->_error_log ? "<br />".$OBJ->_error_log."<br />" : "")."<br />"
				."Phone check result: ".($check_result ? "<b style='color:green;'>Good</b>" : "<b style='color:red;'>Bad</b>");
		}
		// Display form
		$sql = "SELECT id, nick, phone, country 
				FROM ".db('user')." 
				WHERE country IN ('".implode("','", $OBJ->_cc_to_verify)."') 
					AND group='3' 
					AND phone != '' 
					AND active='1' 
				";
		list($total) = db()->query_fetch("SELECT COUNT(*) AS 0 FROM (".$sql.") AS tmp");
		$sql .= "ORDER BY id DESC 
				LIMIT 1000";
		$A = db()->query_fetch_all($sql);
		foreach ((array)$A as $B){
			$select_array[$B["id"]] = _prepare_html("(ID:".$B["id"].") ".$B["nick"]." (".$B["phone"].") (".$B["country"].")");
		}
		$replace = array(
			"action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"user_box" 	=> common()->select_box("user", $select_array, "", " ", 2, "", false),
			"total"		=> intval($total),
		);		
		return tpl()->parse($_GET["object"]."/identify_form", $replace);
	}

	/**
	* Do force phone verify
	*/
	function force_verify () {
		if ($_SESSION["admin_group"] != 1) {
			return _e("Tih action only for admin with group=1");
		}
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$A = db()->query_fetch("SELECT * FROM ".db('log_maxmind_phone_verify')." WHERE id='".$_GET["id"]."'");
		$escort_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE id=".$A["owner_id"]);
		// Check if user's country is not allowed to verify phone
		$OBJ = &main()->init_class("maxmind_phone_verify", "classes/");
		if (!is_object($OBJ) || !$OBJ->_country_allowed($escort_info["country"])) {
			return _e("Sorry, verification is not available. confirm phone: verify this country unavailable, country: ".$escort_info["country"].", ".$escort_info["id"]);
		}
		// Check for empty phone number
		if (empty($escort_info["phone"])) {
			return _e("Phone number is empty (".$escort_info["phone"].").");
		}
		// Check if number format is correct
		if (!$OBJ->_check_phone_format($escort_info["phone"])) {
			$country_name	= _prepare_html(_country_name($escort_info["country"]));
			$_call_codes	= main()->get_data("call_codes");
			$country_phone_code = $_call_codes[$escort_info["country"]];

			return _e(
				"wrong phone format, phone number: ".$escort_info["phone"].", ".$escort_info["id"].
				"<br />Your phone number seems to be missing country code 
				(it should be +".$country_phone_code." for ".$country_name."). 
				Please be sure to check and correct your phone nubmer in your profile form prior to going further. 
				Phone number should have the following format: +country_code area_code phone_number."
			);
		}
		// Check number of tries
		$num_phone_checks = db()->query_num_rows(
			"SELECT * FROM ".db('log_maxmind_phone_verify')." 
			WHERE check_type='v' 
				AND ref_id != '' 
				AND owner_id=".intval($escort_info["id"])
		);
		// Limit verifies reached, stop here
		if ($num_phone_checks >= 3) {
			return _e(
				"reached max phone checks, num checks: ".$num_phone_checks.", phone number: ".$escort_info["phone"].", ".$escort_info["id"]
			);
		}
		// Have phone verify tries but not reached limit
		if ($num_phone_checks && $num_phone_checks < $this->PARENT_OBJ->ALLOWED_PHONE_CHECKS) {
			$_have_tried_notice = "You can try to verify your phone once again. 
				Please, be very attentive, since the number of attempts is limited. 
				First, check if your phone number ("._prepare_html($escort_info["phone"]).") is correct, including your country and area codes";
		}
		// Do call maxmind
		$OBJ->IDENTIFY_PHONE	= true;
		$OBJ->VERIFY_PHONE		= true;

		$_phone_check_result = $OBJ->_send_request($escort_info["phone"], $escort_info["id"], $escort_info["country"]);

		return "<br /><br /><div>called maxmind, result: ".$_phone_check_result.", phone number: ".$escort_info["phone"].", ".$escort_info["id"]."</div>".
				"<br /><br />Action log:<hr /><br />".print_r($OBJ->_action_log, 1).
				"<br /><br />Error log:<hr /><br />".print_r($OBJ->_error_log, 1);
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
			"check_type"	=> 'select_box("check_type",	$this->_check_type_full,	$selected, 1, 2, "", false)',
			"phone_type"	=> 'select_box("phone_type",	$this->_phone_type_select,	$selected, 0, 2, "", false)',
			"success"		=> 'select_box("success",		$this->_success,			$selected, 0, 2, "", false)',

		));
		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}
		// Sort orders & success
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
   		$this->_success		= array(
			-1	=> "",
			"1" => "Yes",
			"0" => "No",
		);
		// Sort fields
		$this->_sort_by = array(
			"",
			"user_id",
			"date",
			"check_type",
			"phone_type",
			"verify_code",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_id",
			"check_type",
			"phone_type",
			"verify_code",
			"success",
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
		if (!isset($SF["success"])) {
			$SF["success"] = -1;
		}
		if (!isset($SF["phone_type"])) {
			$SF["phone_type"] = -1;
		}
		// Generate filter for the common fileds
		if ($SF["user_id"])			 	$sql .= " AND owner_id = ".intval($SF["user_id"])." \r\n";
		if ($SF["success"] != -1)		$sql .= " AND success = '".intval($SF["success"])."' \r\n";
		if ($SF["check_type"])			$sql .= " AND check_type = '"._es($SF["check_type"])."' \r\n";
		if (strlen($SF["verify_code"])) $sql .= " AND verify_code  LIKE '%".intval($SF["verify_code"])."%' \r\n";
		if ($SF["phone_type"] && $SF["phone_type"] != -1) {
			$sql .= " AND phone_type = '"._es($SF["phone_type"])."' \r\n";
		}
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY ".$this->_sort_by[$SF["sort_by"]]." \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based filter
	function _show_filter () {
		$SF = &$_SESSION[$this->_filter_name];
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = _prepare_html($SF[$name]);
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $SF[$item_name]);
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
				"name"	=> "Identify Phone",
				"url"	=> "./?object=".$_GET["object"]."&action=force_identify",
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
		$pheader = t("Maxmind Phone Verify Service Log Viewer");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"force_identify"		=> "Identify Phone",
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
