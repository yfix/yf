<?php

//-----------------------------------------------------------------------------
// Main search methods for sub queries
class sub_queries {

	/** @var mixed @conf_skip */
	public $_active_fields	= null;
	/** @var mixed @conf_skip */
	public $users			= null;
	/** @var mixed @conf_skip */
	public $categories		= null;
	/** @var mixed @conf_skip */
	public $photos			= null;
	/** @var mixed @conf_skip */
	public $total			= null;
	/** @var mixed @conf_skip */
	public $num_per_page	= null;
	/** @var mixed @conf_skip */
	public $cur_page		= null;
	/** @var mixed @conf_skip */
	public $start_record	= null;
	/** @var mixed @conf_skip */
	public $end_record		= null;
	/** @var mixed @conf_skip */
	public $c				= null; // Counter

	//-----------------------------------------------------------------------------
	// Constructor
	function sub_queries() {
		$this->PARENT_OBJ = module('search');
		define("SEARCH_CLASS_NAME", "search");
	}

	//-----------------------------------------------------------------------------
	// Show searching results
	function _do_search ($request_array = "", $query_ads = "", $query_users = "") {
		// For shorter code
		$AF = $this->_active_fields = $request_array;
		// Prepare city
		if (!empty($AF["city"])) {
			$AF["city"] = ucwords(str_replace("_", " ", $AF["city"]));
		}
		// Get categories and cities
		_get_categories();
		_get_cities();
		// Check gender
		$this->_allowed_genders = array(
			"male",
			"female",
			"transsexual"
		);
		$AF["sex"] = strtolower($AF["sex"]);
		if (!in_array($AF["sex"], $this->_allowed_genders)) {
			$AF["sex"] = "";
		}
		// Process gender
		if (empty($AF["sex"]) && (!empty($AF["male"]) || !empty($AF["female"]) || !empty($AF["transsexual"]))) {
			// Check for all values
			if (!empty($AF["male"]) && !empty($AF["female"]) && !empty($AF["transsexual"])) {
				$genders_all = true;
			}
			if (!$genders_all) {
				$AF["genders"] = array();
				if (!empty($AF["male"]))		$AF["genders"][] = "Male";
				if (!empty($AF["female"]))		$AF["genders"][] = "Female";
				if (!empty($AF["transsexual"]))	$AF["genders"][] = "Transsexual";
			}
		}
		// Process race
		if (empty($AF["race"]) && (!empty($AF["white"]) || !empty($AF["black"]) || !empty($AF["asian"]))) {
			// Check for all values
			if (!empty($AF["white"]) && !empty($AF["black"]) && !empty($AF["asian"])) {
				$races_all = true;
			}
			if (!$races_all) {
				$AF["race"] = array();
				if (!empty($AF["white"])) $AF["race"][] = "Caucasian (White)";
				if (!empty($AF["black"])) $AF["race"][] = "Black";
				if (!empty($AF["asian"])) $AF["race"][] = "Asian";
			}
		}
		// Process agency status
		if (empty($AF["agency_status"]) && (!empty($AF["independents"]) || !empty($AF["agency_employees"]) || !empty($AF["agencies"]))) {
			// Check for all values
			if (!empty($AF["independents"]) && !empty($AF["agency_employees"]) && !empty($AF["agencies"])) {
				$agency_status_all = true;
			}
			if (!$agency_status_all) {
				$AF["agency_status"] = array();
				if (!empty($AF["independents"]))		$AF["agency_status"][] = 1;
				if (!empty($AF["agency_employees"]))	$AF["agency_status"][] = 2;
				if (!empty($AF["agencies"]))			$AF["agency_status"][] = 3;
			}
		}
		// Get current per page value
		$this->num_per_page = in_array($AF["per_page"], $this->PARENT_OBJ->_per_page) ? $AF["per_page"] : (defined("SITE_MAX_ADS_PER_PAGE") ? SITE_MAX_ADS_PER_PAGE : 10);
		// Get current page number
		$AF["page"] = !empty($AF["page"]) ? intval($AF["page"]) : 0;
		$this->cur_page = $AF["page"] ? $AF["page"] : 1;
		// Determine start and end records
		$this->start_record	= ($this->cur_page > 1) ? ($this->cur_page - 1) * $this->num_per_page : 1;
		$this->end_record	= $this->start_record + $this->num_per_page - 1;
		$this->c			= $this->start_record != 1 ? $this->start_record + 1 : 1; // Initial counter value
		// Fetch ads from database
		$Q = db()->query($this->_generate_sql_for_ads($AF, $query_ads, $query_users));
		while ($A = db()->fetch_assoc($Q)) {
			$ads[$A["ad_id"]] = $A;
		}
		// Get unique user ids from ads
		if (is_array($ads)) {
			// Count total records
			list($this->total) = db()->query_fetch("SELECT FOUND_ROWS() AS `0`", false);
			$this->total = intval($this->total);
		}
		// Add city_priority_placement ads
		if ($_GET["object"] == "category" && !empty($AF["city"])) {
			// Resolve city id
			$obj = module('site_nav_bar');
			if (is_object($obj)) {
				$cur_city_id	= $obj->_get_city_id_by_name($AF["city"]);
			}
			// Try to find paid orders for this city
			if (!empty($cur_city_id)) {
				$Q = db()->query("SELECT * FROM ".db('adv_orders')." WHERE service_id=8 AND city_id=".intval($cur_city_id)." AND status=1 ORDER BY add_date DESC");
				while ($A = db()->fetch_assoc($Q)) {
					$paid_ads_ids[$A["ad_id"]] = $A["ad_id"];
				}
			}
			// Get paid ads infos
			if (!empty($paid_ads_ids)) {
				$Q = db()->query("SELECT * FROM ".db('ads')." WHERE ad_id IN(".implode(",", $paid_ads_ids).")");
				while ($A = db()->fetch_assoc($Q)) {
					$paid_ads_infos[$A["ad_id"]] = $A;
				}
			}
			// Put paid ads on top
			if (!empty($paid_ads_infos)) {
				$ads = array_merge($paid_ads_infos, (array)$ads);
			}
		}
		// Get unique user ids from ads
		if (is_array($ads)) {
			// Limit number of processing records (if needed)
			if (!empty($AF["limit"]) && $this->total > $AF["limit"]) {
				$this->total = $AF["limit"];
			}
			// Process users
			foreach ((array)$ads as $A) {
				$user_ids[$A["user_id"]]	= $A["user_id"];
			}
			// Fetch users info's
			$Q2 = db()->query($this->_generate_sql_for_users($AF, $user_ids, false));
			while ($A = db()->fetch_assoc($Q2)) {
				$this->users[$A["id"]] = $A;
			}
			// Set global current escort info for use by other modules
			if ($this->PARENT_OBJ->PERSONAL_ADS_MODE && !empty($this->users[$AF["user_id"]])) {
				$GLOBALS['cur_escort_info'] = $this->users[$AF["user_id"]];
			}
			// Try to count photos for the specified ads
			$Q4 = db()->query(
				"SELECT COUNT(id) AS num_photos,user_id 
				FROM ".db('gallery_photos')." 
				WHERE user_id IN(".implode(",",$user_ids).") 
				GROUP BY user_id"
			);
			while ($A = db()->fetch_assoc($Q4)) {
				$this->_users_num_photos[$A["user_id"]] = $A["num_photos"];
			}
			$items = "";
			// Get sub advert items (only for cities and categories)
			$SUB_ADVERT = array();
			if ($_GET["object"] == "category") {
				$SUB_ADVERT = $this->PARENT_OBJ->SUB_ADVERT_IN_RESULTS;
			}
			// Process records
			$result_array		= array();
			$sub_counter		= 0;
			$_displayed_users	= array();
			$per_page			= $this->num_per_page ? $this->num_per_page : $this->PARENT_OBJ->RECORDS_LIMIT;
			foreach ((array)$ads as $ad_info) {
				// Provide unique users
				if (!empty($AF["unique_users"])) {
					if (isset($_displayed_users[$ad_info["user_id"]])) {
						continue;
					} else {
						$_displayed_users[$ad_info["user_id"]] = $ad_info["user_id"];
					}
				}
				$sub_counter++;
				// Force limit number of result items
				if (!empty($AF["limit"]) && ++$i > $AF["limit"]) {
					break;
				}
				// Check if we reached number of records on one page
				if ($sub_counter > $per_page) {
					break;
				}
				// Special array need to process ad record correctly
				$user_info = array(
					"id"				=> $ad_info["user_id"],
					"user_name"			=> _display_name($this->users[$ad_info["user_id"]]),
					"city"				=> $this->users[$ad_info["user_id"]]["city"],
					"url"				=> $this->users[$ad_info["user_id"]]["url"],
					"recip_url"			=> $this->users[$ad_info["user_id"]]["recip_url"],
					"search_keywords"	=> !empty($AF["keywords"]) ? $AF["keywords"] : "",
					"search_user_name"	=> !empty($AF["user_name"]) ? $AF["user_name"] : "",
					"num_photos"		=> $this->_users_num_photos[$ad_info["user_id"]],
					"counter"			=> $this->c++,
					"sex"				=> strtolower($this->users[$ad_info["user_id"]]["sex"]),
					"photo_verified"	=> $this->users[$ad_info["user_id"]]["photo_verified"],
				);
				$GLOBALS['verified_photos'][$user_info["id"]] = $user_info["photo_verified"];
				// Return result as array
				if ($GLOBALS['search_result_as_array']) {
					$result_array[$ad_info["ad_id"]] = array_merge((array)$ad_info, (array)$user_info);
					continue;
				}
				// Process record
				$items .= $this->PARENT_OBJ->_show_ad_record($ad_info, $user_info);
				// Display sub advert item if needed
				if (!empty($SUB_ADVERT[$sub_counter]) && $sub_counter < $this->num_per_page) {
					$replace = array(
						"advert_text"	=> $SUB_ADVERT[$sub_counter],
					);
					$items .= tpl()->parse("search/sub_advert_item", $replace);
				}
			}
			// Process pages
			$pages = $this->_show_pages();
			$replace4 = array(
				"total_results"	=> $this->total,
				"first_num"		=> $this->start_record > 1 ? $this->start_record + 1 : 1,
				"last_num"		=> $this->c - 1,
				"search_time"	=> round(microtime(true) - main()->_time_start, 2),
			);
			$result_info = tpl()->parse(SEARCH_CLASS_NAME."/result_info", $replace4);
			$header_text = !empty($GLOBALS['search_header_text']) ? $GLOBALS['search_header_text'] : t("Search Results");
			// Prepare template
			$replace = array(
				"header_text"		=> empty($GLOBALS['search_no_header']) ? $header_text : "",
				"items"				=> $items,
				"result_info"		=> $result_info,
				"pages"				=> $pages,
				"no_back_button"	=> isset($GLOBALS['search_no_back_button']) ? 1 : "",
				"search_form_short"	=> $this->PARENT_OBJ->_short_search_form($AF),
				"personal_ads_mode"	=> intval((bool)$this->PARENT_OBJ->PERSONAL_ADS_MODE),
				"escort_name"		=> !empty($GLOBALS['cur_escort_info']) ? _prepare_html(_display_name($GLOBALS['cur_escort_info'])) : "",
			);
		} else {
			// Prepare template
			$replace = array(
				"search_form_short"	=> $this->PARENT_OBJ->_short_search_form($AF),
				"personal_ads_mode"	=> intval((bool)$this->PARENT_OBJ->PERSONAL_ADS_MODE),
			);
		}
		// Return result as array
		if ($GLOBALS['search_result_as_array']) {
			return $result_array;
		} else {
			// Common result set (HTML)
			return tpl()->parse(SEARCH_CLASS_NAME."/".(!empty($items) ? "result_main" : "no_matches"), $replace);
		}
	}

	//-----------------------------------------------------------------------------
	// Show pages section for the results
	function _show_pages () {
		// Do not display 1 page
		if ($this->total <= $this->num_per_page) {
			return false;
		}
		// Generate query text according to existing fields
		$q = "";
		foreach ((array)$this->_active_fields as $k => $v) {
			if ($k != "page") $q .= "&".$k."=".$v;
		}
		// Base path to the pages
		$path = empty($GLOBALS['search_pages_url']) ? "./?object=".SEARCH_CLASS_NAME.$q."&q=results" : $GLOBALS['search_pages_url'];
		// Connect standard pager
		$tmp = $_GET["page"];
		$_GET["page"] = $this->cur_page;
		list(, $pages, ) = common()->divide_pages(null, $path, null, $this->num_per_page, $this->total, "", 0);
		$_GET["page"] = $tmp;
		return $pages;
	}

	//-----------------------------------------------------------------------------
	// Generate SQL query for ads
	function _generate_sql_for_ads ($AF = array(), $query_ads = "", $query_users = "") {
		// Create SQL for ads
		$sql1 = "SELECT SQL_CALC_FOUND_ROWS 
					ad_id,cat_id,user_id,sex,subject,descript,url,add_date".$add_sql." 
				FROM ".db('ads')." 
				WHERE 1=1 ".
					($this->PARENT_OBJ->DISPLAY_ONLY_ACTIVE ? " AND status IN('active'".($this->PARENT_OBJ->DISPLAY_EXPIRED ? ",'expired'" : "").")" : "");
		// Try to use query rewrite
		if (!empty($query_ads) || !empty($query_users)) {
			if (!empty($query_ads)) {
				$sql1 .= " AND ". $query_ads;
			}
			if (!empty($query_users)) {
				// Create subquery for users
				$sql1 .= " AND user_id IN (".$this->_generate_sql_for_users ($AF, "", true, $query_users).")";
			}
		} else {
			// Generate additional SQL query parts
			if (!empty($AF["keywords"])) {
				// Determine condition
				$condition = $AF["condition"] == "or" ? " OR " : " AND ";
				// Try to get keywords words
				$keywords_array = explode(" ", $AF["keywords"]);
				foreach ((array)$keywords_array as $k => $v) {
					$v = trim($v);
					if (empty($v)) {
						continue;
					}
					$subject_array[]	= " subject LIKE '%"._es($v)."%' ";
					$descript_array[]	= " descript LIKE '%"._es($v)."%' ";
				}
				if (is_array($subject_array)) {
					$sql1 .= " AND ((".implode($condition, $subject_array).") OR (".implode($condition, $descript_array)."))";
				}
			}
			if (!empty($AF["user_id"]))	{
				$sql1 .= " AND user_id = ".intval($AF["user_id"]);
			}
			if (!empty($AF["required_url"])) {
				$sql1 .= " AND url != '' AND url != 'http://'";
			}
			// Hack for the correct display inside cities
			$obj = module('site_nav_bar');
			if (!empty($AF["city"]) && is_object($obj)) {
				$cur_city_id	= $obj->_get_city_id_by_name($AF["city"]);
				$city_cat_id	= $GLOBALS['cities'][$cur_city_id]['cat_id'];
				if (!empty($city_cat_id)) {
					$sql1 .= " AND cat_id = ".intval($city_cat_id);
				}
			} elseif (!empty($AF["cat_id"])) {
				$sql1 .= " AND cat_id = ".intval($AF["cat_id"]);
			}
			// Process add date field
			if (!empty($AF["before_date"])) {
				$sql1 .= " AND add_date <= ".strtotime($AF["before_date"]);
			}
			if (!empty($AF["after_date"])) {
				$sql1 .= " AND add_date > ".strtotime($AF["after_date"]);
			}
			// Do not include expired ads
			if (!$this->PARENT_OBJ->DISPLAY_EXPIRED) {
				$sql1 .= " AND exp_date >= ".time();
			}
			// Create subquery for users
			$sql_for_users = $this->_generate_sql_for_users ($AF, "", true, $query_users);
			if (!empty($sql_for_users)) {
				$sql1 .= " AND user_id IN (".$sql_for_users.")";
			}
			// Subquery for photos
			if (!empty($AF["required_photo"])) {
				$sql1 .= " AND user_id IN (SELECT DISTINCT(user_id) FROM ".db('gallery_photos')." WHERE active='1')";
			}
		}
		// Process search limits if needed
		if (!empty($GLOBALS["SEARCH_LIMITS"]["ADS"])) {
			foreach ((array)$GLOBALS["SEARCH_LIMITS"]["ADS"] as $ads_limits_sql) {
				$sql1 .= " ".$ads_limits_sql."\r\n";
			}
		}
		// Limit parent category ID
		if (!empty($GLOBALS["PARENT_CAT_LIMIT"])) {
			$sql1 .= " AND cat_id IN (SELECT id FROM ".db('category')." WHERE parent_id=".intval($GLOBALS["PARENT_CAT_LIMIT"]).")";
		}
		// Process state
		if (!empty($AF["state"])) {
			$sql1 .= " AND cat_id IN (SELECT id FROM ".db('category')." WHERE state_code = '".$AF["state"]."')";
		}
		// Process country
		if (!empty($AF["country"])) {
			// Try to get category
			$country_cat_id = 0;
			$AF["country"] = strtolower($AF["country"]);
			foreach ((array)$GLOBALS['categories'] as $_cat_id => $v) {
				if (strtolower($v["name"]) == $AF["country"]) {
					$country_cat_id = $_cat_id;
					break;
				}
			}
			if (!empty($country_cat_id)) {
				$_cur_cat_info = $GLOBALS['categories'][$country_cat_id];
				// Top-level category
				if (!empty($_cur_cat_info) && $_cur_cat_info["parent_id"] == 0) {
					// Get child categories
					$_cats_array = array($country_cat_id => $country_cat_id);
					foreach ((array)$GLOBALS['categories'] as $_id => $_info) {
						if ($_info["parent_id"] != $country_cat_id) {
							continue;
						}
						$_cats_array[$_id] = $_id;
					}
					if (!empty($_cats_array)) {
						$sql1 .= " AND cat_id IN(".implode(",",$_cats_array).")";
					}
				} else {
					$sql1 .= " AND cat_id=".intval($country_cat_id);
				}
			} else {
				$sql1 .= " AND cat_id IN (SELECT id FROM ".db('category')." WHERE country_code = '".strtoupper($AF["country"])."')";
			}
		}
		// Process activities
		if (!empty($AF["activities"])) {
			$sql1 .= " AND user_id IN (SELECT user_id FROM ".db('prof_keywords')." WHERE keywords LIKE '%;".intval($AF["activities"]).";%')";
		}
		// Process geo
		$AF["geo_lon"] = floatval($AF["geo_lon"]);
		$AF["geo_lat"] = floatval($AF["geo_lat"]);
		if (!empty($AF["geo_lon"]) && !empty($AF["geo_lat"]) && !empty($AF["geo_radius"])) {
			$geo_distance = "(POW((69.1 * (lon - ".floatval($AF["geo_lon"]).") * cos(".floatval($AF["geo_lat"])." / 57.3)), '2') + POW((69.1 * (lat - ".floatval($AF["geo_lat"]).")), '2'))";
			$sql1 .= " AND lon != 0 AND ".$geo_distance." < (".floatval($AF["geo_radius"])." * ".floatval($AF["geo_radius"]).") ";
		}
		// Filter only ads from within same country as the owner
		if (!empty($AF["same_country"])) {
			$sql1 .= " AND same_country = 1 ";
		}
		// Filter only ads from within same location as the owner
		if (!empty($AF["same_location"])) {
			$sql1 .= " AND same_location = 1 ";
		}
		// Process sorting order
		if (!empty($GLOBALS['search_force_order_by'])) {
			$order_by_sql = $GLOBALS['search_force_order_by']." ".$AF["order"];
		} else {
			$order_by_sql = "";
			// Geo distance sort
			if (!empty($geo_distance)) {
				$order_by_sql .= $geo_distance." ASC, ";
			}
			// Category priority priority
			$order_by_sql .= "cat_priority DESC";
			// Try to get custom sort order
			if (isset($this->PARENT_OBJ->_order_by[$AF["order_by"]]) && isset($this->PARENT_OBJ->_order[$AF["order"]])) {
				$order_by_sql .= " ,".$AF["order_by"]." ".$AF["order"];
				// Default sorting order
			} else {
				if (!empty($AF["cat_id"])) {
					$cat_info = $GLOBALS['categories'][$AF["cat_id"]];
				}
				$order_by_sql .= " ,same_country DESC "
					.($cat_info["state_code"] ? ",same_location DESC" : "")
					.", rnd DESC";
			}
		}
		if (!empty($order_by_sql)) {
			$sql1 .= " ORDER BY ".$order_by_sql." ";
		}
		// Optimization for speed (need to be tested additionally)
		$per_page = $this->num_per_page ? $this->num_per_page : $this->PARENT_OBJ->RECORDS_LIMIT;
		// Get some more records than planned to have ability to skip some wrong
		$per_page = round($per_page * 1.5, 0);

		$sql1 .= " LIMIT ".intval($this->start_record != 1 ? $this->start_record : 0).", ".intval($per_page);
		return $sql1;
	}

	//-----------------------------------------------------------------------------
	// Generate SQL query for users
	function _generate_sql_for_users ($AF = array(), $user_ids = array(), $for_subquery = false, $query_users = "") {
		// Check if other params needed
		if (!$for_subquery) {
			$sql1 = "SELECT id,nick,name,city,state,country,url,recip_url,sex,photo_verified FROM ".db('user')." WHERE id IN(".implode(",",$user_ids).")";
			return $sql1;
		} else {
			$sql1 = "SELECT id FROM ".db('user')." WHERE id != 0 ";
			$start_sql = $sql1;
		}
		// Try to use query rewrite
		if (!empty($query_users)) {
			$sql1 .= " AND ". $query_users;
		} else {
			// Process other elements
			if (!empty($AF["user_id"]))	{
				$sql1 .= " AND id = ".intval($AF["user_id"]);
			}
			// Process several genders
			if (!empty($AF["genders"]) && is_array($AF["genders"])) {
				foreach ((array)$AF["genders"] as $_cur_gender) {
					if (!in_array(strtolower($_cur_gender), $this->_allowed_genders)) {
						continue;
					}
					$tmp_genders_array[] = " sex='"._es($_cur_gender)."' ";
				}
				if (!empty($tmp_genders_array)) {
					$sql1 .= " AND (".implode(" OR ", $tmp_genders_array).") ";
				}
			// We checked sex field before, do not care here
			} elseif (!empty($AF["sex"])) {
				$sql1 .= " AND sex = '".$AF["sex"]."'";
			}
			if (!empty($AF["user_name"])) {
				$sql1 .= " AND nick LIKE '%".$AF["user_name"]."%'";
			}
			// Hack for the correct display inside cities
			$obj = module("site_nav_bar");
			if (!empty($AF["city"]) && is_object($obj)) {
				$cur_city_id	= $obj->_get_city_id_by_name($AF["city"]);
				$city_sql		= $GLOBALS['cities'][$cur_city_id]['sql_user'];
				if (!empty($city_sql)) {
					$sql1 .= " AND ".$city_sql." ";
				}
			} else {
				if (!empty($AF["city"])) {
					$sql1 .= " AND city='".$AF["city"]."'";
				}
			}
			if (!empty($AF["state"])) {
				$sql1 .= " AND state='".$AF["state"]."'";
			}
			if (!empty($AF["country"])) {
				$sql1 .= " AND country='".$AF["country"]."'";
			}
			if (!empty($AF["age1"]) && in_array($AF["age1"], $this->PARENT_OBJ->_ages)) {
				$sql1 .= " AND age>=".$AF["age1"];
			}
			if (!empty($AF["age2"]) && in_array($AF["age2"], $this->PARENT_OBJ->_ages)) {
				$sql1 .= " AND age<=".$AF["age2"];
			}
			if (!empty($AF["height1"]) && isset($this->PARENT_OBJ->_heights[$AF["height1"]]) && isset($this->PARENT_OBJ->_heights[$AF["height2"]])) {
				$sql1 .= " AND height >= ".$AF["height1"]." AND height <= ".$AF["height2"];
			}
			if (!empty($AF["weight1"]) && isset($this->PARENT_OBJ->_weights[$AF["weight1"]]) && isset($this->PARENT_OBJ->_weights[$AF["weight2"]])) {
				$sql1 .= " AND weight >= ".$AF["weight1"]." AND weight <= ".$AF["weight2"];
			}
			if (!empty($AF["hair_color"]) && isset($this->PARENT_OBJ->_hair_colors[$AF["hair_color"]])) {
				$sql1 .= " AND hair_color='".$AF["hair_color"]."'";
			}
			if (!empty($AF["eye_color"]) && isset($this->PARENT_OBJ->_eye_colors[$AF["eye_color"]])) {
				$sql1 .= " AND eye_color='".$AF["eye_color"]."'";
			}
			if (!empty($AF["orientation"]) && isset($this->PARENT_OBJ->_orientations[$AF["orientation"]])) {
				$sql1 .= " AND orientation='".$AF["orientation"]."'";
			}
			if (!empty($AF["star_sign"]) && isset($this->PARENT_OBJ->_star_signs[$AF["star_sign"]])) {
				$sql1 .= " AND star_sign='".$AF["star_sign"]."'";
			}
			if (!empty($AF["smoking"]) && isset($this->PARENT_OBJ->_smoking[$AF["smoking"]])) {
				$sql1 .= " AND smoking='".$AF["smoking"]."'";
			}
			if (!empty($AF["race"]) && is_array($AF["race"])) {
				foreach ((array)$AF["race"] as $cur_race) {
					if (!isset($this->PARENT_OBJ->_races[$cur_race])) {
						continue;
					}
					$tmp_races_array[] = " race='"._es($cur_race)."' ";
				}
				if (!empty($tmp_races_array)) {
					$sql1 .= " AND (".implode(" OR ", $tmp_races_array).") ";
				}
			} elseif (!empty($AF["race"]) && isset($this->PARENT_OBJ->_races[$AF["race"]])) {
				$sql1 .= " AND race='".$AF["race"]."'";
			}
			// Process agency status
			$agency_statuses_sql = array(
				1 => " agency_id = 0 AND `group` = 3 ", // Independent escort
				2 => " agency_id != 0 AND `group` = 3 ", // Agency employee
				3 => " `group` = 4", // Pure agency
			);
			if (!empty($AF["agency_status"]) && is_array($AF["agency_status"])) {
				foreach ((array)$AF["agency_status"] as $cur_status) {
					if (!isset($this->PARENT_OBJ->_agency_statuses[$cur_status])) {
						continue;
					}
					$tmp_ag_statuses_array[] = " (".$agency_statuses_sql[$cur_status].") ";
				}
				if (!empty($tmp_ag_statuses_array)) {
					$sql1 .= " AND (".implode(" OR ", $tmp_ag_statuses_array).") ";
				}
			} elseif (!empty($AF["agency_status"]) && isset($this->PARENT_OBJ->_agency_statuses[$AF["agency_status"]])) {
				$sql1 .= " AND ".$agency_statuses_sql[$AF["agency_status"]];
			}
			// Search by ZIP code (US only)
			if (!empty($AF['zip_code']) && (strlen($AF['zip_code']) == 5)) {
				$ZIP_CODES_OBJ = main()->init_class("zip_codes", "classes/");
				$radius = (intval($AF['miles']) > 0) ? intval($AF['miles']) : 20;
				$sql_zip = $ZIP_CODES_OBJ->_generate_sql($AF['zip_code'], $radius);
				if (strlen($sql_zip)) {
					$sql1 .= " AND zip_code IN (".$sql_zip.") AND country='US'";
				}
			}
			// Show only users with avatars
			if (!empty($AF["w_avatars_only"])) {
				$sql1 .= " AND has_avatar = '1' ";
			}
		}
		// Process search limits if needed
		if (!empty($GLOBALS["SEARCH_LIMITS"]["USERS"])) {
			foreach ((array)$GLOBALS["SEARCH_LIMITS"]["USERS"] as $users_limits_sql) {
				$sql1 .= " ".$users_limits_sql."\r\n";
			}
		}
		// Slightly optimize speed (do not include results with all users)
		if ($sql1 == $start_sql) {
			$sql1 = "";
		}
		return $sql1;
	}
}
