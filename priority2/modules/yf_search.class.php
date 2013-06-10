<?php

//-----------------------------------------------------------------------------
// Search with ability to use subqueries (for MySQL >= 4.1.x and other RDBMS)
class yf_search {

	/** @var int Min number of symbols to process */
	public $MIN_KEYWORD_LENGTH		= 3;
	/** @var int Max number of symbols to process */
	public $MAX_KEYWORD_LENGTH		= 64;
	/** @var int Records limit */
	public $RECORDS_LIMIT			= 100;
	/** @var bool Highlight matched keywords or user_name */
	public $HIGHLIGHT_MATCHES		= true;
	/** @var bool @conf_skip Sub_query SQL mode */
	public $SUBQUERY_MODE			= true;
	/** @var mixed @conf_skip Custom highlighting method */
	public $_highlight_method		= null;
	/** @var bool Display only active ads */
	public $DISPLAY_ONLY_ACTIVE	= 1;
	/** @var bool Display also expired ads (works only when DISPLAY_ONLY_ACTIVE == 1) */
	public $DISPLAY_EXPIRED		= 0;
	/** @var bool Personal ads mode */
	public $PERSONAL_ADS_MODE		= false;
	/** @var array */
	public $_default_search_params = array();

	/**
	* Constructor (PHP 4.x)
	*
	* @access	private
	* @return	void
	*/
	function search () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @access	private
	* @return	void
	*/
	function __construct () {
		if (in_array($_GET["object"], array("category"))) {
			$this->_default_search_params = array(
				"unique_users" => 1,
			);
		}
	}

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Boxes used in the form
		$this->_boxes = array(
			"state"			=> 'select_box("state",			$this->_states,			$selected, " ", 2, "", 0)',
			"country"		=> 'select_box("country",		$this->_countries,		$selected, 0, 2, "", 0)',
			"sex"			=> 'select_box("sex",			$this->_sex,			$selected, 0, 2, "", 0)',
			"age1"			=> 'select_box("age1",			$this->_ages,			$selected, " ", 2, "", 0)',
			"age2"			=> 'select_box("age2",			$this->_ages,			$selected, " ", 2, "", 0)',
			"orientation"	=> 'select_box("orientation",	$this->_orientations,	$selected, " ", 2, "", 0)',
			"race"			=> 'select_box("race",			$this->_races,			$selected, " ", 2, "", 0)',
			"star_sign"		=> 'select_box("star_sign",		$this->_star_signs,		$selected, " ", 2, "", 0)',
			"smoking"		=> 'select_box("smoking",		$this->_smoking,		$selected, " ", 2, "", 0)',
			"english"		=> 'select_box("english",		$this->_english,		$selected, " ", 2, "", 0)',
			"height1"		=> 'select_box("height1",		$this->_heights,		$selected, " ", 2, "", 0)',
			"height2"		=> 'select_box("height2",		$this->_heights,		$selected, " ", 2, "", 0)',
			"weight1"		=> 'select_box("weight1",		$this->_weights,		$selected, " ", 2, "", 0)',
			"weight2"		=> 'select_box("weight2",		$this->_weights,		$selected, " ", 2, "", 0)',
			"hair_color"	=> 'select_box("hair_color",	$this->_hair_colors,	$selected, " ", 2, "", 0)',
			"eye_color"		=> 'select_box("eye_color",		$this->_eye_colors,		$selected, " ", 2, "", 0)',
			"order_by"		=> 'select_box("order_by",		$this->_order_by,		$selected, 0, 2, "", 0)',
			"order"			=> 'select_box("order",			$this->_order,			$selected, 0, 2, "", 0)',
			"per_page"		=> 'select_box("per_page",		$this->_per_page,		$selected, 0, 2, "", 0)',
			"condition"		=> 'radio_box("condition",		$this->_conditions,		"and"/*$selected*/)',
			"agency_status"	=> 'select_box("agency_status",	$this->_agency_statuses,$selected, " ", 2, "", 0)',
			"miles"			=> 'select_box("miles",			$this->_miles,			$selected, false, 2, "", false)',
			"activities"	=> 'select_box("activities",	$this->_activities,		$selected, " ", 2, "", false)',
		);
		// Array of text fields
		$this->_text_fields	= array(
			"user_name",
			"user_id",
			"keywords",
			"city",
			"required_photo",
			"required_url",
			"page",
			"cat_id",
			"before_date",
			"after_date",
			"limit",
			"male",
			"female",
			"transsexual",
			"white",
			"black",
			"asian",
			"independents",
			"agency_employees",
			"agencies",
			"zip_code",
			"geo_lon",
			"geo_lat",
			"geo_radius",
			"w_avatars_only",
			"unique_users",
			"same_country",
		);
		// Connect common used arrays
		include (INCLUDE_PATH."common_code.php");
		// Specific for the current module arrays
		$this->_sex = array_merge(array(" " => t("All")), $this->_sex);
		$this->_order_by = array(
			"add_date"		=> t("Add date"),
			"rates_hour"	=> t("Rates per hour"),
			"views"			=> t("# views"),
			"visits"		=> t("# visits"),
		);
		$this->_conditions = array(
			"and"	=> "all words (AND)",
			"or"	=> "any word (OR)",
		);
		$this->_order = array("DESC" => t("Descending"), "ASC" => t("Ascending"));
		$this->_per_page = array(10=>10,25=>25,50=>50);
		// Prepare miles array for zip_code search
		$this->_miles = main()->get_data("miles");
		// Array of keywords
		$this->_activities	= main()->get_data("activities");
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Display results
		if (isset($_GET["q"]) || isset($_POST["q"])) {
			return $this->_do_search();
		}
		// Display form
		$replace = array(
			"users_search_link"		=> "./?object=users_search",
			"ads_search_link"		=> "./?object=ads&action=search",
			"reviews_search_link"	=> "./?object=reviews&action=search",
			"blogs_search_link"		=> "./?object=blog&action=search",
			"galleries_search_link"	=> "./?object=gallery&action=search",
			"articles_search_link"	=> "./?object=articles&action=search",
			"forum_search_link"		=> "./?object=forum&action=search",
			"interests_search_link"	=> "./?object=interests&action=search",
		);
		return tpl()->parse(__CLASS__."/form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show search form
	function _search_form () {
		$replace = array(
			"form_action"			=> "./?object=search",
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, !empty($_POST[$item_name]) ? $_POST[$item_name] : "");
		}
		// Process text fields
		foreach ((array)$this->_text_fields as $item_name) {
			$replace[$item_name] = $_POST[$item_name];
		}
		return tpl()->parse(__CLASS__."/ads_search_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// 
	function _short_search_form ($AF = array()) {
		if ($_GET["object"] != "search") {
			return false;
		}
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=search",
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, !empty($AF[$item_name]) ? $AF[$item_name] : "");
		}
		// Process text fields
		foreach ((array)$this->_text_fields as $item_name) {
			$replace[$item_name] = $_POST[$item_name];
		}
		return tpl()->parse(SEARCH_CLASS_NAME."/ads_search_form_short", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show searching results
	function _do_search ($request_array = "", $query_ads = "", $query_users = "") {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		// Prepare default request params
		foreach ((array)$this->_default_search_params as $k => $v) {
			$request_array[$k] = trim($v);
		}
		// Cleanup request vars
		if (!is_array($request_array)) {
			$request_array = array_merge($_GET, $_POST);
		}
		foreach ((array)$request_array as $k => $v) {
			$request_array[$k] = trim($v);
		}
		// Array of all allowed field names
		$fields = array_merge(array_keys($this->_boxes), $this->_text_fields);
		// Try to grab non-empty fields
		foreach ((array)$fields as $field_name) {
			if (isset($request_array[$field_name]) && strlen($request_array[$field_name])) {
				$active_fields[$field_name] = _es(stripslashes($request_array[$field_name]));
			}
		}
		// Check if we inside "escort_ads" mode (different output for displaying personal ads)
		if (count($active_fields) == 1 && !empty($active_fields["user_id"])) {
			$this->PERSONAL_ADS_MODE = true;
		}
		// If no header needed - do not show it then
		if (!empty($request_array["no_header"])) {
			$GLOBALS['search_no_header'] = true;
		} else {
			// Try to assign custom header automatically
			$this->_auto_header();
		}
		// Set result as array if needed
		if (!empty($request_array["as_array"])) {
			$GLOBALS['search_result_as_array'] = 1;
		}
		if (!empty($request_array["order_by"])) {
			$GLOBALS['search_force_order_by'] = $request_array["order_by"];
		}
		// Init sub class
		$S = main()->init_class(($this->SUBQUERY_MODE ? "sub" : "std")."_queries", INCLUDE_PATH."modules/search/");
		return is_object($S) ? $S->_do_search($active_fields, $query_ads, $query_users) : false;
	}

	//-----------------------------------------------------------------------------
	// Show ad record contents
	function _show_ad_record ($ad_info = array(), $user_info = array()) {
		$display_name	= $user_info["user_name"];
		$user_name		= substr($display_name, 0, SITE_NAME_LENGTH) . (strlen($display_name) > SITE_NAME_LENGTH ? "..." : "");
		$title			= substr($ad_info["subject"], 0, SITE_TITLE_LENGTH) . (strlen($ad_info["subject"]) > SITE_TITLE_LENGTH ? "..." : "");
		$desc			= strip_tags($ad_info["descript"]);
		$desc			= substr($desc, 0, SITE_DESCR_LENGTH) . (strlen($desc) > SITE_DESCR_LENGTH ? "..." : "");
		$escort_link	= "./?object=escort&action=show&id=".$ad_info["ad_id"]."&cat_id=".$ad_info["cat_id"];
		// Highlight results (if needed)
		if ($this->HIGHLIGHT_MATCHES) {
			if (strlen($user_info["search_keywords"])) {
				$title	= highlight($title, $user_info["search_keywords"]);
				$desc	= highlight($desc, $user_info["search_keywords"]);
			}
			// Highlight user name if needed
			if (strlen($user_info["search_user_name"])) {
				$user_name	= highlight($user_name, $user_info["search_user_name"]);
			}
		}
		// Custom highlighting here
		if (!empty($GLOBALS["highlight_method"])) {
			$desc = eval("return ".$GLOBALS["highlight_method"]."(\$desc);");
		}
		// Connect to category display module
		$CATEGORY_OBJ = main()->init_class("category");
		// Try to get current location
		if ($_GET["object"] == "category") {
			if (!empty($_GET["city"])) {
				$cur_location = $_GET["city"];
			} elseif (!empty($_GET["cat_name"])) {
				$cur_location = $_GET["cat_name"];
			}
		}
		// Process template
		$replace = array(
			"number"		=> $user_info["counter"],
			// DO NOT ADD _prepare_html here !!!
			"escort_name"	=> $user_name. " - ". $title,
			"escort_link"	=> $escort_link,
			"escort_text"	=> $desc,
			"admin_options"	=> "",
			"num_photos"	=> intval($user_info["num_photos"]),
			"add_date"		=> _format_date($ad_info["add_date"], "long"),
			"cat_nav"		=> $CATEGORY_OBJ->_cat_navigation($ad_info["cat_id"], $user_info["sex"], $user_info["city"], false),
			"user_avatar"	=> _show_avatar ($user_info["id"], $user_info["search_user_name"], 1, 0, 0, $escort_link),
			"location"		=> _prepare_html($cur_location),
		);
		return tpl()->parse(__CLASS__."/ads_item", $replace);
	}

	//-----------------------------------------------------------------------------
	// Set custom header text
	function _set_custom_header_text ($text = "") {
		$GLOBALS['search_header_text'] = str_replace("_", " ", $text);
	}

	//-----------------------------------------------------------------------------
	// Set custom method to highlight text
	function _set_highlight_method ($method_name = "") {
		if (!empty($method_name)) $GLOBALS["highlight_method"] = $method_name;
	}

	//-----------------------------------------------------------------------------
	// Set custom pages url
	function _set_custom_pages_url ($url = "") {
		$GLOBALS['search_pages_url'] = $url;
	}

	//-----------------------------------------------------------------------------
	// Set back button status
	function _set_no_back_button () {
		$GLOBALS['search_no_back_button'] = true;
	}

	//-----------------------------------------------------------------------------
	// Try to auto-assign custom header text
	function _auto_header () {
		// Do not process custom headers for the common
		if ($_REQUEST["object"] == "search") {
			return false;
		}
		// Go forward
		$sex = isset($_REQUEST["sex"]) ? $_REQUEST["sex"] : "Female";
		if (strlen($_REQUEST["cat_name"])) {
			$text = tpl()->parse(__CLASS__."/custom_header_cat", array("sex" => $sex, "cat_name" => $_REQUEST["cat_name"]));
		} elseif (strlen($_REQUEST["city"])) {
			$text = tpl()->parse(__CLASS__."/custom_header_city", array("sex" => $sex, "city_name" => $_REQUEST["city"]));
		}
		// Set custom header info
		if (strlen($text) && !strlen($GLOBALS['search_header_text'])) {
			$GLOBALS['search_header_text'] = str_replace("_", " ", $text);
		}
	}

	//-----------------------------------------------------------------------------
	// Display forum search form
	function _show_forum_search_form () {
		$FORUM_OBJ = main()->init_class("forum");
		$FORUM_SEARCH_OBJ = main()->init_class("forum_search", FORUM_MODULES_DIR);
		// For correct search address
		$old_action = $_GET["action"];
		$_GET["action"] = "search";
		// Display form
		$body = is_object($FORUM_SEARCH_OBJ) ? $FORUM_SEARCH_OBJ->_search_form(__CLASS__."/forum_search_form") : "";
		$_GET["action"] = $old_action;
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Display reviews search form
	function _show_reviews_search_form () {
		$REVIEWS_SEARCH_OBJ = main()->init_class("reviews_search");
		return is_object($REVIEWS_SEARCH_OBJ) ? $REVIEWS_SEARCH_OBJ->_search_form(1, __CLASS__."/reviews_search_form") : "";
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
//-----------------------------------------------------------------------------
