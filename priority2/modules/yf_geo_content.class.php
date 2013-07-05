<?php

//-----------------------------------------------------------------------------
// Content display by geo location (ads, pages, etc)
class yf_geo_content {

// TODO: need to connect here in all methods where needed
	/** @var bool  */
	public $AJAX_USE_CACHE = false;

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		define("GEO_CONTENT_CLASS_NAME", "geo_content");
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
		return $this->change_location();
	}

	//-----------------------------------------------------------------------------
	// JavaScript - based city select (3-step) method
	function _city_select ($params = array()) {
		$STPL_NAME = "register/city_select";
		if (!empty($params["stpl_name"])) {
			$STPL_NAME = $params["stpl_name"];
		}
		// Prepare default values
		if (!isset($_POST["country"])) {
			$_POST["country"]	= $params["sel_country"];
		}
		if (!isset($_POST["region"])) {
			$_POST["region"]	= $params["sel_region"];
		}
		if (!isset($_POST["city"])) {
			$_POST["city"]		= $params["sel_city"];
		}
		// Fill array of countries
		$_countries	= main()->get_data("countries");
		// Process featured countries if needed
		if (FEATURED_COUNTRY_SELECT == 1) {
			$_featured_countries = main()->get_data("featured_countries");
			if (!empty($_featured_countries)) {
				$_countries = array_merge(array("  " => "  "), $_featured_countries, $_countries);
			}
		}
		// Get regions and cities for selected
		if (!empty($_POST["country"])) {
			$_regions[""] = "  --   Please select region   --  ";
			$Q = db()->query(
				"SELECT * 
				FROM ".db('geo_regions')." 
				WHERE country = '"._es($_POST["country"])."' 
				ORDER BY name ASC"
			);
			while ($A = db()->fetch_assoc($Q)) $_regions[$A["code"]] = $A["name"];
			// Maybe country without regions ?
			if (count($_regions) == 1) {
				$have_no_regions = true;
			}
			// Get cities list
			if (strlen($_POST["region"])) {
				$_cities[""] = "  --   Please select city   --  ";
				$Q = db()->query(
					"SELECT * 
					FROM ".db('geo_city_location')." 
					WHERE region = '"._es($_POST["region"])."' 
						AND country = '"._es($_POST["country"])."' 
						AND city != ''
					GROUP BY city
					ORDER BY city ASC"
				);
				while ($A = db()->fetch_assoc($Q)) $_cities[$A["city"]] = $A["city"];
			}
		}
		// Prepare template
		$replace = array(
			"country_box"	=> common()->select_box("country",	$_countries,	$_POST["country"], false, 2, "", false),
			"region_box"	=> common()->select_box("region",		$_regions,		$_POST["region"], false, 2, "", false),
			"city_box"		=> common()->select_box("city",		$_cities,		$_POST["city"], false, 2, "", false),
			"sel_country"	=> $_POST["country"],
			"sel_region"	=> $_POST["region"],
			"sel_city"		=> $_POST["city"],
			"ajax_link"		=> process_url("./?object=".GEO_CONTENT_CLASS_NAME."&action=ajax_city"),
			"sel_no_regions"=> intval($have_no_regions),
		);
		return tpl()->parse($STPL_NAME, $replace);
	}

	//-----------------------------------------------------------------------------
	// Get selected array
	function ajax_city () {
		main()->NO_GRAPHICS = true;
		// Check input
		if (isset($_REQUEST["country"])) {
			// Process featured countries
			if (FEATURED_COUNTRY_SELECT && substr($_REQUEST["country"], 0, 2) == "f_") {
				$_REQUEST["country"] = substr($_REQUEST["country"], 2);
			}
			if (!preg_match("/^[a-z]{2}\$/i", $_REQUEST["country"])) {
				unset($_REQUEST["country"]);
			}
		}
		if (isset($_REQUEST["region"])) {
			if (!preg_match("/^[a-z0-9]{2}\$/i", $_REQUEST["region"])) {
				$_REQUEST["region"] = "";
			}
		}
		// Get Zip code
		if (!empty($_REQUEST["city"]) && strlen($_REQUEST["region"]) && strtoupper($_REQUEST["country"]) == "US") {
			$Q = db()->query(
				"SELECT postal_code 
				FROM ".db('geo_city_location')." 
				WHERE region = '"._es($_REQUEST["region"])."' 
					AND country = '"._es($_REQUEST["country"])."' 
					AND city = '"._es($_REQUEST["city"])."' 
					AND postal_code != '' 
				GROUP BY postal_code 
				ORDER BY postal_code ASC"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$body .= "<option value=\""._prepare_html($A["postal_code"])."\">"._prepare_html($A["postal_code"])."</option>\n";
			}
			if (!empty($body)) {
				$body = "<option name=\"\">  --   Please select zip code   --  </option>\n". $body;
			}
		// Get city
		} elseif (strlen($_REQUEST["region"]) && !empty($_REQUEST["country"])) {
			if ($this->AJAX_USE_CACHE && main()->USE_SYSTEM_CACHE) {
				$CACHE_NAME = "cities_".$_REQUEST["country"]."_".$_REQUEST["region"];
// TODO: need to add "_mkdir_m" in "sys_cahe->put" (to allow sub-folders "/" in cache names)
				$cities = main()->get_data($CACHE_NAME);
			}
			if (empty($cities)) {
				$Q = db()->query(
					"SELECT * 
					FROM ".db('geo_city_location')." 
					WHERE region = '"._es($_REQUEST["region"])."' 
						AND country = '"._es($_REQUEST["country"])."'
						AND city != ''
					GROUP BY city
					ORDER BY city ASC"
				);
				while ($A = db()->fetch_assoc($Q)) {
					$cities[$A["loc_id"]] = $A["city"];
				}
				if ($this->AJAX_USE_CACHE && main()->USE_SYSTEM_CACHE) {
					cache()->put($CACHE_NAME, $cities);
				}
			}
			foreach ((array)$cities as $_city_name) {
				// We need UTF-8 here
				$city_utf8 = common()->_convert_charset($_city_name);
				if (strlen($city_utf8)) {
					$_city_name = $city_utf8;
				}
				$body .= "<option value=\""._prepare_html($_city_name)."\">"._prepare_html($_city_name)."</option>\n";
			}
			if (!empty($body)) {
				$body = "<option name=\"\">  --   Please select city   --  </option>\n". $body;
			}
		// Get region
		} elseif (!empty($_REQUEST["country"])) {
			$Q = db()->query(
				"SELECT * 
				FROM ".db('geo_regions')." 
				WHERE country = '"._es($_REQUEST["country"])."'
				ORDER BY name ASC"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$body .= "<option value=\"".$A["code"]."\">"._prepare_html($A["name"])."</option>\n";
			}
			if (!empty($body)) {
				$body = "<option name=\"\">  --   Please select region   --  </option>\n". $body;
			}
		}
		echo $body;
	}

	//-----------------------------------------------------------------------------
	// Change user location
	function change_location () {
		if (!main()->USE_GEO_IP) {
			return false;
		}
		// Current system selected
		$sel_data = main()->_USER_GEO_DATA;
		// Detected by IP
		$cur_ip	= common()->get_ip();
		$ip_data = common()->_get_geo_data_from_db($cur_ip);
		// Fill array of countries
		$_countries	= main()->get_data("countries");
		// Process featured countries if needed
		if (FEATURED_COUNTRY_SELECT == 1) {
			$_featured_countries = main()->get_data("featured_countries");
			if (!empty($_featured_countries)) {
				$_countries = array_merge(array("  " => "  "), $_featured_countries, $_countries);
			}
		}
		// Get regions and cities for selected
		if (!empty($sel_data["country_code"])) {
			$_regions[""] = "  --   Please select region   --  ";
			$Q = db()->query(
				"SELECT * 
				FROM ".db('geo_regions')." 
				WHERE country = '"._es($sel_data["country_code"])."' 
				ORDER BY name ASC"
			);
			while ($A = db()->fetch_assoc($Q)) $_regions[$A["code"]] = $A["name"];
			// Maybe country without regions ?
			if (count($_regions) == 1) {
				$have_no_regions = true;
			}
			// Get cities list
			if (strlen($sel_data["region_code"])) {
				$_cities[""] = "  --   Please select city   --  ";
				$Q = db()->query(
					"SELECT * 
					FROM ".db('geo_city_location')." 
					WHERE region = '"._es($sel_data["region_code"])."' 
						AND country = '"._es($sel_data["country_code"])."' 
						AND city != ''
					GROUP BY city
					ORDER BY city ASC"
				);
				while ($A = db()->fetch_assoc($Q)) $_cities[$A["city"]] = $A["city"];
			}
			// Get zip codes list
			if (strtoupper($sel_data["country_code"]) == "US" && strlen($sel_data["region_code"]) && !empty($sel_data["city_name"]) && !empty($sel_data["zip_code"])) {
				$_zip_codes[""] = "  --   Please select zip code   --  ";
				$Q = db()->query(
					"SELECT postal_code 
					FROM ".db('geo_city_location')." 
					WHERE region = '"._es($sel_data["region_code"])."' 
						AND country = '"._es($sel_data["country_code"])."' 
						AND city = '"._es($sel_data["city_name"])."' 
						AND postal_code != '' 
					GROUP BY postal_code 
					ORDER BY postal_code ASC"
				);
				while ($A = db()->fetch_assoc($Q)) $_zip_codes[$A["postal_code"]] = $A["postal_code"];
			}
		}
		// Check if user profile's city is in our db
		if (!empty($this->_user_info["country"]) && strlen($this->_user_info["state"]) && !empty($this->_user_info["city"])) {
			$check_profile_city = db()->query_fetch(
				"SELECT * 
				FROM ".db('geo_city_location')." 
				WHERE region = '"._es($this->_user_info["state"])."' 
					AND country = '"._es($this->_user_info["country"])."' 
					AND city = '"._es($this->_user_info["city"])."' 
				LIMIT 1"
			);
			if (empty($check_profile_city)) {
				$this->_user_info["city"] = "";
			}
		}
		// Save data
		if (!empty($_POST)) {
			// Update user info
			if (isset($_POST["update_2"]) && $this->USER_ID && !empty($_POST["update_profile"]) && !empty($sel_data["city_name"])) {
				if ($sel_data["country_code"] != $this->_user_info["country"]
					|| $sel_data["region_code"] != $this->_user_info["state"]
					|| strtolower($sel_data["city_name"]) != strtolower($this->_user_info["city"])
				) {
					$sql_array = array(
						"country"	=> _es($sel_data["country_code"]),
						"state"		=> _es($sel_data["region_code"]),
					);
					if (!empty($sel_data["city_name"])) {
						$sql_array["city"]		= _es($sel_data["city_name"]);
					}
					if (!empty($sel_data["zip_code"]) && is_numeric($sel_data["zip_code"])) {
						$sql_array["zip_code"]	= _es($sel_data["zip_code"]);
					}
					update_user($this->USER_ID, $sql_array);
				}
				return js_redirect("./?object=account");
			}
			// Update current geo location
			$something_changed = false;
			// Switch between selected
			if ($_POST["geo_type"] == "by_ip") {
				$data_to_save = array(
					"country_code"	=> $ip_data["country_code"],
					"country_name"	=> $ip_data["country_name"],
					"region_code"	=> $ip_data["region_code"],
					"city_name"		=> $ip_data["city_name"],
					"longitude"		=> $ip_data["longitude"],
					"latitude"		=> $ip_data["latitude"],
					"zip_code"		=> strtoupper($ip_data["country_code"]) == "US" ? $ip_data["zip_code"] : "",
				);
			} elseif ($_POST["geo_type"] == "by_profile") {
				$data_to_save = array(
					"country_code"	=> $this->_user_info["country"],
					"country_name"	=> _country_name($this->_user_info["country"]),
					"region_code"	=> $this->_user_info["state"],
					"city_name"		=> $this->_user_info["city"],
					"longitude"		=> $this->_user_info["lon"],
					"latitude"		=> $this->_user_info["lat"],
					"zip_code"		=> strtoupper($this->_user_info["country"]) == "US" ? $this->_user_info["zip_code"] : "",
				);
			} elseif ($_POST["geo_type"] == "by_other") {
				if (FEATURED_COUNTRY_SELECT == 1 && substr($_POST["country"], 0, 2) == "f_") {
					$_POST["country"] = substr($_REQUEST["country"], 2);
				}
				if (strtoupper($_POST["country"]) != "US") {
					$_POST["zip_code"] = "";
				}
				if (false !== strpos(strtolower($_POST["city"]), "please select city")) {
					$_POST["city"] = "";
				}
				// Check if city is in our db
				if (!empty($_POST["country"]) && strlen($_POST["region"]) && !empty($_POST["city"])) {
					$CITY_EXISTS = db()->query_fetch(
						"SELECT * 
						FROM ".db('geo_city_location')." 
						WHERE country = '"._es($_POST["country"])."' 
							AND region = '"._es($_POST["region"])."'
							AND city = '"._es($_POST["city"])."'
						LIMIT 1 "
					);
					if (empty($CITY_EXISTS)) {
						$_POST["city"] = "";
					}
				}
				$VERIFY_OK = false;
				// Get lon and lat for the given data (also verify posted data)
				if (!empty($_POST["country"])) {
					// Check if we have country without known regions
					list($have_regions) = db()->query_fetch(
						"SELECT COUNT(*) AS 0 
						FROM ".db('geo_regions')." 
						WHERE country='"._es($_POST["country"])."'"
					);
					if ($have_regions && strlen($_POST["region"])) {
						$city_lon_lat = db()->query_fetch(
							"SELECT * 
							FROM ".db('geo_city_location')." 
							WHERE country = '"._es($_POST["country"])."' 
								AND region = '"._es($_POST["region"])."'
								".(!empty($_POST["city"]) ? " AND city = '"._es($_POST["city"])."' " : "")."
								".($_POST["zip_code"] ? " AND (postal_code='"._es($_POST["zip_code"])."' OR postal_code = '') " : "")."
							LIMIT 1 "
						);
					}
					// Try to get lon and lat from region
					if (empty($city_lon_lat)) {
						$city_lon_lat = db()->query_fetch(
							"SELECT * 
							FROM ".db('geo_city_location')." 
							WHERE country = '"._es($_POST["country"])."' 
								".(strlen($_POST["region"]) ? " AND region = '"._es($_POST["region"])."' " : "")."
							LIMIT 1 "
						);
					}
					if (!empty($city_lon_lat)) {
						$lon = $city_lon_lat["longitude"];
						$lat = $city_lon_lat["latitude"];
						$VERIFY_OK = true;
					}
				}
				if ($VERIFY_OK) {
					$data_to_save = array(
						"country_code"	=> $_POST["country"],
						"country_name"	=> _country_name($_POST["country"]),
						"region_code"	=> $_POST["region"],
						"city_name"		=> $_POST["city"],
						"longitude"		=> $lon,
						"latitude"		=> $lat,
						"zip_code"		=> strtoupper($_POST["country"]) == "US" ? $_POST["zip_code"] : "",
					);
				}
			}
			// Save preferences inside cookie
			if (!empty($data_to_save)) {
				if (!is_numeric($data_to_save["zip_code"])) {
					$data_to_save["zip_code"] = "";
				}
				$result = setcookie("geo_selected", serialize($data_to_save), time() + 2592000, "/");
			}
			return js_redirect("./?object=".GEO_CONTENT_CLASS_NAME."&action=change_location_result");
		}
		// Prepare template
		$replace = array(
			"form_action"		=> "./?object=".GEO_CONTENT_CLASS_NAME."&action=".__FUNCTION__,
			"ajax_link"			=> process_url("./?object=".GEO_CONTENT_CLASS_NAME."&action=ajax_city"),

			"country_box"		=> common()->select_box("country",	$_countries,	$sel_data["country_code"],	false, 2, "", false),
			"region_box"		=> common()->select_box("region",		$_regions,		$sel_data["region_code"],	false, 2, "", false),
			"city_box"			=> common()->select_box("city",		$_cities,		$sel_data["city_name"],		false, 2, "", false),
			"zip_box"			=> common()->select_box("zip_code",	$_zip_codes,	$sel_data["zip_code"],		false, 2, "", false),

			"sel_country"		=> _prepare_html($sel_data["country_name"]),
			"sel_country_code"	=> _prepare_html($sel_data["country_code"]),
			"sel_region"		=> _prepare_html(_region_name($sel_data["region_code"], $sel_data["country_code"])),
			"sel_region_code"	=> _prepare_html($sel_data["region_code"]),
			"sel_city"			=> _prepare_html($sel_data["city_name"]),
			"sel_zip_code"		=> _prepare_html($sel_data["zip_code"]),

			"ip_country"		=> _prepare_html($ip_data["country_name"]),
			"ip_country_code"	=> _prepare_html($ip_data["country_code"]),
			"ip_region"			=> _prepare_html(_region_name($ip_data["region_code"], $ip_data["country_code"])),
			"ip_region_code"	=> _prepare_html($ip_data["region_code"]),
			"ip_city"			=> _prepare_html($ip_data["city_name"]),
			"ip_zip_code"		=> _prepare_html($ip_data["zip_code"]),

			"p_country"			=> $this->_user_info["country"] ? _prepare_html(_country_name($this->_user_info["country"])) : "",
			"p_country_code"	=> $this->_user_info["country"] ? _prepare_html($this->_user_info["country"]) : "",
			"p_region"			=> $this->_user_info["state"]	? _prepare_html(_region_name($this->_user_info["state"], $this->_user_info["country"])) : "",
			"p_region_code"		=> $this->_user_info["state"]	? _prepare_html($this->_user_info["state"]) : "",
			"p_city"			=> $this->_user_info["city"]	? _prepare_html($this->_user_info["city"]) : "",
			"p_zip_code"		=> $this->_user_info["zip_code"]? _prepare_html($this->_user_info["zip_code"]) : "",

			"sel_by_cookie"		=> !empty($sel_data) && $sel_data["_source"] == "sel_cookie" ? 1 : 0,
			"sel_no_regions"	=> intval($have_no_regions),
			"empty_selection"	=> empty($sel_data["country_code"]) && empty($sel_data["region_code"]) && empty($sel_data["city_name"]) ? 1 : 0,
		);
		return tpl()->parse(GEO_CONTENT_CLASS_NAME."/change_location_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Change user location
	function change_location_result () {
		if (!main()->USE_GEO_IP) {
			return false;
		}
		// Current system selected
		$sel_data = main()->_USER_GEO_DATA;
		if (empty($sel_data)) {
			return _e(t("Internal error #036. Please contact site admin."));
		}
		// Check if something has changed
		if ($this->USER_ID && !empty($sel_data)) {
			if (($sel_data["country_code"] && $sel_data["country_code"] != $this->_user_info["country"])
				|| ($sel_data["region_code"] && $sel_data["region_code"] != $this->_user_info["state"])
				|| ($sel_data["city_name"] && strtolower($sel_data["city_name"]) != strtolower($this->_user_info["city"]))
				|| ($sel_data["zip_code"] && $sel_data["zip_code"] != $this->_user_info["zip_code"])
			) {
				$something_changed = 1;
			}
		}
		// Prepare template
		$replace2 = array(
			"form_action"		=> "./?object=".GEO_CONTENT_CLASS_NAME."&action=change_location",
			"show_update_form"	=> $this->USER_ID && $something_changed ? 1 : 0,
			"city"				=> _prepare_html($sel_data["city_name"]),
			"region"			=> _prepare_html(_region_name($sel_data["region_code"], $sel_data["country_code"])),
			"country"			=> _prepare_html(_country_name($sel_data["country_code"])),
			"zip"				=> _prepare_html($sel_data["zip_code"]),
		);
		return tpl()->parse(GEO_CONTENT_CLASS_NAME."/change_location_success", $replace2);
	}
}
