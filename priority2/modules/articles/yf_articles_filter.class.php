<?php

/**
* Articles search filter handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_articles_filter {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->PARENT_OBJ		= module(ARTICLES_CLASS_NAME);
		// Prepare data
		if ($this->PARENT_OBJ->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		if (!$this->PARENT_OBJ->USE_FILTER) return "";
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Create qubquery for the user table
		if ($SF["account_type"]) {
			$user_sub_sql .= " AND `group` = ".intval($SF["account_type"])." ";
		}
		if (strlen($SF["gender"])) {
			$user_sub_sql .= " AND `sex` ='"._es($SF["gender"])."' ";
		}
		if (strlen($SF["race"])) {
			$user_sub_sql .= " AND `race` ='"._es($SF["race"])."' ";
		}
		if (strlen($SF["country"])) {
			$user_sub_sql .= " AND `country` ='"._es($SF["country"])."' ";
		}
		if (strlen($SF["nick"])) {
			$user_sub_sql .= " AND `nick` LIKE '"._es($SF["nick"])."%' ";
		}
		if (strlen($SF["state"])) {
			$user_sub_sql .= " AND `state` LIKE '"._es($SF["state"])."%' ";
		}
		if (strlen($SF["city"])) {
			$user_sub_sql .= " AND `city` LIKE '"._es($SF["city"])."%' ";
		}
		if (!empty($SF["user_id"])) {
			$user_sub_sql .= " AND `id`=".intval($SF["user_id"])." ";
		}
		if (!empty($user_sub_sql)) {
			$sql .= " AND `user_id` IN (SELECT `id` FROM `".db('user')."` WHERE 1=1 ".$user_sub_sql.") \r\n";
		}
		if (strlen($SF["title"])) {
			$sql .= " AND `title` LIKE '"._es($SF["title"])."%' \r\n";
		}
		if (strlen($SF["full_text"])) {
			$sql .= " AND `full_text` LIKE '%"._es($SF["full_text"])."%' \r\n";
		}
		if (!empty($SF["cat_id"])) {
			$sql .= " AND `cat_id`=".intval($SF["cat_id"])." \r\n";
		}
		if (strlen($SF["author_name"])) {
			$sql .= " AND `author_name` LIKE '"._es($SF["author_name"])."%' \r\n";
		}
		// Sorting here
		if ($SF["sort_by"])		$sql .= " ORDER BY `".$this->_sort_by[$SF["sort_by"]]."` \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	/**
	* Prepare filter data
	*/
	function _prepare_filter_data () {
/*
		if (!$this->PARENT_OBJ->USE_FILTER || !in_array($_GET["action"], array(
			"clear_filter",
			"save_filter",
			"search",
		))) return "";
*/
		// Filter session array name
		$this->_filter_name	= ARTICLES_CLASS_NAME."_filter";
		// Connect common used arrays
		include (INCLUDE_PATH."common_code.php");
		// Array of available filter fields
		$this->_fields_in_filter = array(
			"nick",
			"title",
			"full_text",
			"state",
			"city",
			"sort_by",
			"sort_order",
			"per_page",
			"account_type",
			"race",
			"gender",
			"country",
			"cat_id",
			"user_id",
			"author_name",
		);
		// Prepae boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
//			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
			"per_page"		=> 'select_box("per_page",		$this->_per_page,		$selected, 0, 2, "", 0)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,	$selected, 0, 2, "", false)',
			"race"			=> 'select_box("race",			$this->_races,			$selected, 0, 2, "", false)',
			"gender"		=> 'select_box("gender",		$this->_sex,			$selected, 0, 2, "", false)',
			"country"		=> 'select_box("country",		$this->_countries,		$selected, 0, 2, "", false)',
			"cat_id"		=> 'select_box("cat_id", 		$this->_articles_cats2,	$selected, false, 2, "", false)',
		));
		$this->_articles_cats2 = $this->PARENT_OBJ->CATS_OBJ->_prepare_for_box(null, 1);
		// Number of records per page
		$this->_per_page = array(10=>10,20=>20,50=>50,100=>100);
		// Sort fields
		$this->_sort_by = array("","id","user_id","add_date");
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Process account types
		$this->_account_types	= main()->get_data("account_types");
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		$this->_races			= array_merge(array(" " => t("-- All --")), $this->_races);
		$this->_sex				= array_merge(array(" " => t("-- All --")), $this->_sex);
	}

	/**
	* Session - based filter form stored in $_SESSION[$this->_filter_name][...]
	*/
	function _show_filter () {
		if (!$this->PARENT_OBJ->USE_FILTER) return "";
		$replace = array(
			"save_action"	=> "./?object=".ARTICLES_CLASS_NAME."&action=save_filter".(MAIN_TYPE_ADMIN ? _add_get() : ""),
			"clear_url"		=> "./?object=".ARTICLES_CLASS_NAME."&action=clear_filter".(MAIN_TYPE_ADMIN ? _add_get() : ""),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse(ARTICLES_CLASS_NAME."/search_filter", $replace);
	}

	/**
	* Filter save method
	*/
	function _save_filter ($silent = false) {
		if (!$this->PARENT_OBJ->USE_FILTER) return "";
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_REQUEST["country"]) && substr($_REQUEST["country"], 0, 2) == "f_") {
			$_REQUEST["country"] = substr($_REQUEST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
		}
		if (!$silent) {
			js_redirect("./?object=".ARTICLES_CLASS_NAME."&action=search");
		}
	}

	/**
	* Clear filter
	*/
	function _clear_filter ($silent = false) {
		if (!$this->PARENT_OBJ->USE_FILTER) return "";
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".ARTICLES_CLASS_NAME."&action=search");
		}
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
