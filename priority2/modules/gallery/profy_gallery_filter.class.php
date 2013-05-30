<?php

/**
* Gallery search filter handler
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_gallery_filter {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->GALLERY_OBJ	= module(GALLERY_CLASS_NAME);
		// Prepare data
		if ($this->GALLERY_OBJ->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Prepare filter data
	*/
	function _prepare_filter_data () {
		if (!$this->GALLERY_OBJ->USE_FILTER || !in_array($_GET["action"], array(
			"show",
			"clear_filter",
			"save_filter",
			"show_all_galleries",
			"search",
			"tag",
		))) return "";
		// Filter session array name
		$this->_filter_name	= GALLERY_CLASS_NAME."_filter";
		// Connect common used arrays
		$f = INCLUDE_PATH."common_code.php";
		if (file_exists($f)) {
			include $f;
		}
		// Array of available filter fields
		$this->_fields_in_filter = array(
			"nick",
			"blog_title",
			"state",
			"city",
			"sort_by",
			"sort_order",
			"per_page",
			"account_type",
			"race",
			"gender",
			"country",
			"as_photos",
			"zip_code",
			"tag",
			"name",
		);
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
			"per_page"		=> 'select_box("per_page",		$this->_per_page,		$selected, 0, 2, "", 0)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,	$selected, 0, 2, "", false)',
			"race"			=> 'select_box("race",			$this->_races,			$selected, 0, 2, "", false)',
			"gender"		=> 'select_box("gender",		$this->_sex,			$selected, 0, 2, "", false)',
			"country"		=> 'select_box("country",		$this->_countries,		$selected, 0, 2, "", false)',
			"state"			=> 'select_box("state",			$this->_states,			$selected, " ", 2, "", 0)',
			"as_photos"		=> 'radio_box("as_photos",		$this->_as_photos,		$selected, 0, 2, "", true)',
			"miles"			=> 'select_box("miles",			$this->_miles,			$selected, " ", 2, "", false)',
		));
		// Number of records per page
		$this->_per_page = array(10=>10,20=>20,50=>50,100=>100);
		// Sort fields
/*
		$this->_sort_by = array(
			""				=> "",
			"user_id"		=> "User ID",
			"num_photos"	=> "Number of photos",
		);
*/
		$this->_sort_by = array(
			"date_added"	=> "date added",
			"user_rating"	=> "user rating",
			"image_rating"	=> "image rating",
		);

		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Process account types
		$this->_account_types	= main()->get_data("account_types");
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		$this->_races			= array_merge(array(" " => t("-- All --")), (array)$this->_races);
		$this->_sex				= array_merge(array(" " => t("-- All --")), (array)$this->_sex);
		$this->_as_photos = array(
			0 => "Galleries",
			1 => "Images",
		);
		// Prepare miles array for zip_code search
		$this->_miles = main()->get_data("miles");
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql ($_source_sql = "") {
		if (!$this->GALLERY_OBJ->USE_FILTER) {
			return "";
		}
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) {
			$SF[$k] = trim($v);
		}
		// Prepare photos filter
		if (GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$SF["country"] = GEO_LIMIT_COUNTRY;
		}
		if (strlen($SF["country"])) {
			$sql[] = " AND `p`.`geo_cc` ='"._es($SF["country"])."' ";
		}
		if (strlen($SF["state"])) {
			$sql[] = " AND `p`.`geo_rc` = '"._es($SF["state"])."' ";
		}
		if (strlen($SF["name"])) {
			$sql[] = " AND `p`.`name` LIKE '"._es($SF["name"])."%' ";
			$SF["as_photos"] = 1;
		}
		// Create qubquery for the user table
		if ($SF["account_type"]) {
			$user_sub_sql[] = " AND `u`.`group` = ".intval($SF["account_type"])." ";
		}
		if (strlen($SF["gender"])) {
			$user_sub_sql[] = " AND `u`.`sex` ='"._es($SF["gender"])."' ";
		}
		if (strlen($SF["race"])) {
			$user_sub_sql[] = " AND `u`.`race` ='"._es($SF["race"])."' ";
		}
		if (strlen($SF["nick"])) {
			$user_sub_sql[] = " AND `u`.`nick` LIKE '"._es($SF["nick"])."%' ";
		}
		if (strlen($SF["city"])) {
			$user_sub_sql[] = " AND `u`.`city` LIKE '"._es($SF["city"])."%' ";
		}
		// Search by ZIP code (US only)
		if (!empty($SF['zip_code']) && (strlen($SF['zip_code']) == 5)) {
			$ZIP_CODES_OBJ = main()->init_class("zip_codes", "classes/");
			$radius = (intval($SF['miles']) > 0) ? intval($SF['miles']) : 20;
			$sql_zip = $ZIP_CODES_OBJ->_generate_sql($SF['zip_code'], $radius);
			if (strlen($sql_zip)) {
				$user_sub_sql[] = " AND `u`.`zip_code` IN (".$sql_zip.") AND `country`='US' ";
			}
		}
		if (!empty($user_sub_sql)) {
			$sql[] = implode("\r\n", $user_sub_sql);
			$sql[] = " AND `p`.`user_id` = `u`.`id` ";
		}
		if ($this->GALLERY_OBJ->ALLOW_TAGGING && strlen($SF["tag"])) {
			$_source_sql = str_replace(" AS `p`", " AS `p`,`".db('tags')."` AS `t`", $_source_sql);
			$sql[] = " AND `p`.`id` = `t`.`object_id` ";
			$sql[] = " AND `t`.`object_name`='gallery' ";
			$sql[] = " AND `t`.`text` = '"._es($SF["tag"])."' ";
		}
		$SQL_REPLACE = array();
		// Sorting here
		$sort_sql = "";
		if (!$SF["sort_by"]) {
			$SF["sort_by"]		= "user_rating";
			$SF["sort_order"]	= "DESC";
		}
		if (!empty($SF["sort_by"]) && isset($this->_sort_by[$SF["sort_by"]])) {
			// Search as images
			if ($SF["as_photos"]) {
				$DISPLAY_THRESHOLD = 3;
				if ($SF["sort_by"] == "date_added") {
				 	$sort_sql .= " ORDER BY `p`.`add_date` ";
				} elseif ($SF["sort_by"] == "user_rating") {
					$sort_sql .= " ORDER BY `p`.`priority` ";
				} elseif ($SF["sort_by"] == "image_rating") {
					$sort_sql .= " ORDER BY (`p`.`votes_sum` / `p`.`num_votes`) ";
					if ($DISPLAY_THRESHOLD) {
						$sql[] = " AND `p`.`num_votes` >= ".intval($DISPLAY_THRESHOLD)." ";
					}
				}
			// Search as galleries
			} else {
				if ($SF["sort_by"] == "date_added") {
				 	$sort_sql .= " ORDER BY `u`.`add_date` ";
				} elseif ($SF["sort_by"] == "user_rating") {
					$sort_sql .= " ORDER BY `p`.`priority` ";
				} elseif ($SF["sort_by"] == "image_rating") {
					$sort_sql .= " ORDER BY `avg_vote` ".($SF["sort_order"] ? $SF["sort_order"] : "").", `num_photos` ";
					$sql[] = " AND `p`.`num_votes` > 0 ";
					$SQL_REPLACE["AS `num_photos`"]	= "AS `num_photos`, SUM(`p`.`votes_sum`) / SUM(`p`.`num_votes`) AS `avg_vote` ";
				}
			}
			if (strlen($SF["sort_order"]) && $sort_sql) {
				$sort_sql .= " ".$SF["sort_order"]." ";
			}
		} else {
			$sort_sql = " ORDER BY `p`.`priority` DESC, `num_photos` DESC ";
		}
		// Convert to string
		$sql = implode("\r\n", (array)$sql);

		$SQL_REPLACE["/*__FILTER_SQL__*/"]	= $sql;
		$SQL_REPLACE["/*__SORT_SQL__*/"]	= $sort_sql;

		// Replace filter SQL inside source SQL code (if provided)
		if (!empty($_source_sql)) {
			$sql = str_replace(array_keys($SQL_REPLACE), array_values($SQL_REPLACE), $_source_sql);
			return $sql;
		} else {
			$sql .= $sort_sql;
		}
		return $sql;
	}

	/**
	* Session - based filter form stored in $_SESSION[$this->_filter_name][...]
	*/
	function _show_filter () {
		if (!$this->GALLERY_OBJ->USE_FILTER) {
			return "";
		}
		$SF = &$_SESSION[$this->_filter_name];
		$replace = array(
			"save_action"	=> "./?object=".GALLERY_CLASS_NAME."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".GALLERY_CLASS_NAME."&action=clear_filter"._add_get(),
			"allow_tagging"	=> intval((bool)$this->GALLERY_OBJ->ALLOW_TAGGING),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $SF[$name];
		}
		if (!$SF["sort_by"]) {
			$SF["sort_by"] = "user_rating";
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $SF[$item_name]);
		}
		return tpl()->parse(GALLERY_CLASS_NAME."/search_filter", $replace);
	}

	/**
	* Filter save method
	*/
	function _save_filter ($silent = false) {
		if (!$this->GALLERY_OBJ->USE_FILTER) {
			return "";
		}
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		foreach ((array)$this->_fields_in_filter as $name) {
			$_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=show_all_galleries");
		}
	}

	/**
	* Clear filter
	*/
	function _clear_filter ($silent = false) {
		if (!$this->GALLERY_OBJ->USE_FILTER) {
			return "";
		}
		foreach ((array)$_SESSION[$this->_filter_name] as $name) {
			unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=show_all_galleries");
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
