<?php

//-----------------------------------------------------------------------------
// Class to handle user personal info
class profy_user_info {

	// Additional JavaScript based form validation
	var $_JS_VALIDATION = false;
	// User ID
	var $USER_ID = null;
	// Avatar limits
	var $AVATAR_MAX_WIDTH		= 100;
	var $AVATAR_MAX_HEIGHT		= 100;
	var $AVATAR_MAX_FILE_SIZE	= 500000; // bytes

	//-----------------------------------------------------------------------------
	// Constructor
	function profy_user_info () {
		$this->USER_ID = $_GET['user_id'];
		// Get current user details
		if (!empty($this->USER_ID)) {
			$this->_user_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($this->USER_ID));
		}
		// Fill array of agencies
		$this->_agencies[""] = "";
		foreach ((array)main()->get_data("agencies") as $_agency_id => $_agency_info) {
			$this->_agencies[$_agency_id] = _display_name($_agency_info);
		}
		// Array of select boxes to process

		$this->_boxes = array(
			"status"			=> 'select_box("status",		$this->_statuses, 		$selected, false, 2, "", false)',
			"sex"				=> 'select_box("sex",			$this->_sex, 			$selected, false, 2, "", false)',
			"age"				=> 'select_box("age",			$this->_ages, 			$selected, " ", 2, "", false)',
			"orientation"		=> 'select_box("orientation",	$this->_orientations, 	$selected, " ", 2, "", false)',
			"race"				=> 'select_box("race",			$this->_races, 			$selected, " ", 1, "", false)',
			"star_sign"			=> 'select_box("star_sign",		$this->_star_signs, 	$selected, " ", 2, "", false)',
			"smoking"			=> 'select_box("smoking",		$this->_smoking, 		$selected, " ", 2, "", false)',
			"english"			=> 'select_box("english",		$this->_english, 		$selected, " ", 2, "", false)',
			"height"			=> 'select_box("height",		$this->_heights, 		$selected, " ", 2, "", false)',
			"weight"			=> 'select_box("weight",		$this->_weights, 		$selected, " ", 2, "", false)',
			"hair_color"		=> 'select_box("hair_color",	$this->_hair_colors, 	$selected, " ", 2, "", false)',
			"eye_color"			=> 'select_box("eye_color",		$this->_eye_colors, 	$selected, " ", 2, "", false)',
			"cc_payments"		=> 'select_box("cc_payments",	$this->_cc_payments, 	$selected, false, 2, "", false)',
			"agency"			=> 'select_box("agency",		$this->_agencies, 		$selected, false, 2, "", false)',
//			"birth_date"		=> 'date_box($selected, "1915-".(date("Y") - 17), "_birth")',
			// Admin special boxes
			"account_type"		=> 'select_box("account_type",	$this->_account_types, 	$selected, false, 2, "", 1)',
			// "ban_ads"			=> 'radio_box("ban_ads",		$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_email"			=> 'radio_box("ban_email",		$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_reviews"		=> 'radio_box("ban_reviews",	$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_images"		=> 'radio_box("ban_images",		$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_forum"			=> 'radio_box("ban_forum",		$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_comments"		=> 'radio_box("ban_comments",	$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_blog"			=> 'radio_box("ban_blog",		$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_bad_contact"	=> 'radio_box("ban_bad_contact",$this->_trigger, 		$selected, false, 2, "", false)',
			// "ban_reput"			=> 'radio_box("ban_reput",		$this->_trigger, 		$selected, false, 2, "", false)',
			"active"			=> 'select_box("active", array(t("Disabled"), t("Active")), $selected, false, 2, "", false)',
			"manager"			=> 'select_box("manager",		$this->_managers2,		$selected, false, 2, "", false)',
		);
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
		$this->cur_account_type = $this->_account_types[$this->_user_info["group"]];

		$this->_trigger = array(t("<b style='color:green;'>Allowed</b>"), t("<b style='color:red;'>Banned</b>"));
		// Array of form fields to process
		$this->_text_fields	= array(
			"nick",
			"login",
			"email",
			"zip_code",
			"phone",
			"fax",
			//"icq",
			//"yahoo",
			//"aim",
			//"msn",
			//"jabber",
			//"skype",
			"admin_comments",
			//"show_mail",
			"age",
			"city",
			"state",
			"region",
			"country",
			//"contact_by_email",
		);
		$this->_required_fields	= array("nick");
		if ($this->_user_info["group"] == 2) {
			$this->_text_fields = array_merge($this->_text_fields, array(
				"user_name",
			));
		} elseif ($this->_user_info["group"] == 3) {
			$this->_text_fields = array_merge($this->_text_fields, array(
				"user_name",
				"measurements",
				"recip_url",
			));
		} elseif ($this->_user_info["group"] == 4) {
			$this->_text_fields = array_merge($this->_text_fields, array(
				"agency_name",
				"address",
				"number_escorts",
				"working_hours",
			));
			$this->_required_fields = array_merge($this->_required_fields, array(
				"agency_name"
			));
		}

		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}

		// Get available admin groups
		$this->_admin_groups	= main()->get_data("admin_groups");
		// Get available agency managers
		$Q = db()->query("SELECT * FROM `".db('admin')."`/* WHERE `group`=5*/ ORDER BY `group` ASC, `first_name` ASC, `last_name` ASC");
		while ($A = db()->fetch_assoc($Q2)) {
			$this->_managers[$A["id"]] = _prepare_html($A["first_name"]." ".$A["last_name"]." (".$this->_admin_groups[$A["group"]].")");
		}
		// Prepare managers
		$this->_managers2 = array("" => " ");
		foreach ((array)$this->_managers as $k => $v) {
			$this->_managers2[$k] = $v;
		}
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
		// Get user levels
		$this->_user_levels		= main()->get_data("user_levels");
	}

	//-----------------------------------------------------------------------------
	// This function handle user personal info
	function show () {
		// Process correct steps
		$_REQUEST['step'] = intval($_REQUEST['step']);
		$step = $_REQUEST['step'] ? $_REQUEST['step'] : 1;
		// Check user id
		if (empty($this->_user_info)) {
			return _e("User ID is wrong");
		}

		if(empty($this->cur_account_type)){
			return _e("User account type is wrong");
		}
		// Process given step
		if (in_array($step, range(1,2))) {
			$body = eval("return \$this->_step_".$step."();");
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	//
	function _step_1 ($A = null) {
		if (!$A) $A = $this->_user_info;
		// Create JS array for required fields
		if ($this->_JS_VALIDATION && is_array($this->_required_fields)) {
			foreach ((array)$this->_required_fields as $v) $v2.= "'".$v."', ";
			foreach ((array)$this->_required_fields as $v) $v3.= "'".t($v)."', ";	
			$replace2["js_array_1"]	= substr($v2, 0, -2);
			$replace2["js_array_2"]	= substr($v3, 0, -2);
		}
		$A["account_type"] = $this->_user_info["group"];
		// Get random site info
		if (is_array($this->_sites_info->info))	{
			$SITE_INFO	= array_shift($this->_sites_info->info);
		}
		// Process user avatar
		$avatar_image = "No Photo";
		$DIR_OBJ = main()->init_class("dir", "classes/");
		$avatar_file_path	= $DIR_OBJ->_gen_dir_path($A["id"], INCLUDE_PATH. SITE_AVATARS_DIR , 1, 0777). intval($A["id"]). ".jpg";
		if (file_exists($avatar_file_path)) {
			$replace3 = array(
				"avatar_src"	=> str_replace(INCLUDE_PATH, WEB_PATH, $avatar_file_path),
				"del_url"		=> "./?object=".$_GET["object"]."&action=delete_avatar"._add_get(),
			);
			$avatar_image = tpl()->parse($_GET["object"]."/avatar_image", $replace3);
		}
		// Prepare city select box
		$GEO_OBJ = main()->init_class("geo_content");
		if (is_object($GEO_OBJ)) {
			$city_select = $GEO_OBJ->_city_select(array(
				"sel_country"	=> $this->_user_info["country"],
				"sel_region"	=> $this->_user_info["state"],
				"sel_city"		=> $this->_user_info["city"],
			));
		}
		// Create array to replace inside template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]._add_get(),
			"js_validator"	=> $this->_JS_VALIDATION ? tpl()->parse($_GET["object"]."/js_step2_".$this->cur_account_type, $replace2) : "",
			"js_form_code"	=> $this->_JS_VALIDATION ? " onsubmit=\"return form_check(this);\" " : "",
			"back"			=> tpl()->parse("system/back", $replace),
			"home"			=> tpl()->parse("system/home", array("home_link" => WEB_PATH._add_get())),
			"user_id"		=> $this->USER_ID,
			"avatar"		=> tpl()->parse($_GET["object"]."/avatar_item", array("image" => $avatar_image)),
			"admin_msgs"	=> main()->_execute("admin_messages", "_show_for_user", "user_id=".$this->USER_ID),
			"city_select"	=> $city_select,
		);
		$A["agency"]	= $A["agency_id"];
		$A["manager"]	= $A["manager_id"];
		// Process boxes
		
		

		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $A[$item_name]);
		}
		
		// Fill all other form fields with values
		foreach ((array)$this->_text_fields as $name) {
			$replace[$name] = $A[$name];
		}
		$replace["user_name"]		= $A["name"];
		$replace["agency_name"]		= $A["name"];
		$replace["pswd"]			= $A["password"];
		$replace["recip_url"]		= isset($this->_user_info["recip_url"]) ? $A["recip_url"] : "http://";
		$replace["admin_comments"]	= stripslashes($replace["admin_comments"]);
		$replace["user_group"]		= $A["group"];
		$replace["user_level"]		= $A["level"];
		$replace["user_level_name"]	= $this->_user_levels[$A["level"]];
		// Parse template contents	

		return tpl()->parse($_GET["object"]."/step_1_".$this->cur_account_type, $replace);
	}

	//-----------------------------------------------------------------------------
	//
	function _step_2 () {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		// Load user avatar
		if (!empty($_FILES["avatar"]["size"])) $this->_load_avatar();
		// Validate form fields
		$this->_validate_form();
		// If no errors - continue
		if (!common()->_error_exists()) {
			// Prepare values
			foreach ((array)$_POST as $k => $v) {
				$_POST[$k] = _es($v);
			}
			// General fields
			$sql_array = array_merge((array)$sql_array, array(
				"group"			=> $_POST["account_type"],
				"nick"			=> $_POST["nick"],
				"name"			=> $_POST["name"],
				"password"		=> $_POST["pswd"],
				"zip_code"		=> $_POST["zip_code"],
				"state"			=> !empty($_POST["state"]) ? $_POST["state"] : $_POST["region"],
				"country"		=> !empty($_POST["country"]) ? $_POST["country"] : "",
				"email"			=> $_POST["email"],
				"city"			=> $_POST["city"],
				"sex"			=> $_POST["sex"],
				//"birth_date"	=> $_POST["birth_date"],
				"age"			=> intval($_POST["age"]),
				//"icq"			=> $_POST["icq"],
				//"yahoo"			=> $_POST["yahoo"],
				//"aim"			=> $_POST["aim"],
				//"msn"			=> $_POST["msn"],
				//"jabber"		=> $_POST["jabber"],
				//"skype"			=> $_POST["skype"],
				//"show_mail"		=> intval((bool)$_POST["show_mail"]),
				"has_avatar"	=> _avatar_exists($this->_user_info["id"]) ? 1 : 0,
				//"contact_by_email"	=> intval(!$_POST["contact_by_email"]),
			));
			$sql_array = array_merge((array)$sql_array, array(
				"active"			=> intval($_POST["active"]),
				"admin_comments"	=> _es($_POST["admin_comments"]),
			));
			// Generate SQL for the current user group
			if ($this->_user_info["group"] == 2) {
				$sql_array = array_merge((array)$sql_array, array(
				));
			} elseif ($this->_user_info["group"] == 3) {
				$sql_array = array_merge((array)$sql_array, array(
					"phone"			=> $_POST["phone"],
					"fax"			=> $_POST["fax"],
					"orientation"	=> $this->_orientations[$_POST["orientation"]],
					"race"			=> $this->_races[$_POST["race"]],
					"star_sign"		=> $this->_star_signs[$_POST["star_sign"]],
					"smoking"		=> $this->_smoking[$_POST["smoking"]],
					"english"		=> $this->_english[$_POST["english"]],
					"measurements"	=> $_POST["measurements"],
					"height"		=> intval($_POST["height"]),
					"weight"		=> intval($_POST["weight"]),
					"hair_color"	=> $this->_hair_colors[$_POST["hair_color"]],
					"eye_color"		=> $this->_eye_colors[$_POST["eye_color"]],
					"status"		=> $_POST["status"],
					"agency_id"		=> intval($_POST["agency"]),
					"recip_url"		=> $_POST["recip_url"],
				));
			} elseif ($this->_user_info["group"] == 4) {
				$sql_array = array_merge((array)$sql_array, array(
					"phone"			=> $_POST["phone"],
					"fax"			=> $_POST["fax"],
					"address"		=> $_POST["address"],
					"number_escorts"=> $_POST["number_escorts"],
					"working_hours"	=> $_POST["working_hours"],
					"cc_payments"	=> $_POST["cc_payments"],
					"manager_id"	=> $_POST["manager"],
				));
			}
			// Ban user options
			$sql_array = array_merge((array)$sql_array, array(
				// "ban_ads"			=> intval($_POST["ban_ads"]),
				// "ban_reviews"		=> intval($_POST["ban_reviews"]),
				// "ban_email"			=> intval($_POST["ban_email"]),
				// "ban_images"		=> intval($_POST["ban_images"]),
				// "ban_forum"			=> intval($_POST["ban_forum"]),
				// "ban_comments"		=> intval($_POST["ban_comments"]),
				// "ban_blog"			=> intval($_POST["ban_blog"]),
				// "ban_bad_contact"	=> intval($_POST["ban_bad_contact"]),
				// "ban_reput"			=> intval($_POST["ban_reput"]),
			));
			db()->UPDATE("user", $sql_array, "`id` = ".intval($this->_user_info['id']));
			// Try to update user's geo location
			$GEO_OBJ = main()->init_class("geo_ip", "classes/");
			if (is_object($GEO_OBJ)) {
				$GEO_OBJ->_update_user_geo_location($this->_user_info['id']);
			}
			// Output cache trigger
			if (main()->OUTPUT_CACHING) {
				_class("output_cache")->_exec_trigger(array(
					"user_id"	=> $this->_user_info["id"],
					"sex"		=> $this->_user_info["sex"],
				));
			}
			// Return back
			return js_redirect("./?object=".$_GET["object"]._add_get());
		} else {
			$body .= _e();
			$body .= $this->_step_1();
			return $body;
		}
	}	

	//-----------------------------------------------------------------------------
    // Validate form for steps 3 and 4
	function _validate_form () {
		// Cleanup all $_POST fields
		foreach ((array)$_POST as $k => $v) trim($_POST[$k]);
		// Init default validator
		$VALIDATE_OBJ = main()->init_class("validate", "classes/");
  		// Check location
		$VALIDATE_OBJ->_check_location();
		// Validate birth date
		//$VALIDATE_OBJ->_check_birth_date($this->_user_info["birth_date"]);
		// Special for the agency name
		if ($this->cur_account_type == "agency") {
			if ($_POST["name"] == "") {
				common()->_raise_error(t('Agency name required'));
			}
		} else {
			if ($_POST["nick"] == "") {
				common()->_raise_error(t('User nick required'));
			}
		}
		// Check other fields
		if ($_POST["measurements"] != "") {
			$_POST["measurements"] = strtoupper(trim($_POST["measurements"]));
			if (!_check_measurements($_POST["measurements"])) {
				common()->_raise_error(t('Invalid measurements (example, 36DD-27-32)!'));
			}
		}
		if ($_POST["recip_url"] == "http://" || $_POST["recip_url"] == "") {
			$_POST["recip_url"] = "";
		} elseif (!preg_match('#^http://[_a-z0-9-]+\\.[_a-z0-9-]+#i', $_POST["recip_url"])) {
			common()->_raise_error(t('Invalid reciprocal URL'));
		}
	}

	//-----------------------------------------------------------------------------
	// Delete avatar from server
	function delete_avatar () {
		$user_id = intval($_GET["user_id"]);
		if (!empty($user_id)) {
			// Get random site info
			if (is_array($this->_sites_info->info))	{
				$SITE_INFO	= array_shift($this->_sites_info->info);
			}
			// Process user avatar
			$DIR_OBJ = main()->init_class("dir", "classes/");
			$avatar_file_path = $DIR_OBJ->_gen_dir_path($user_id, INCLUDE_PATH. SITE_AVATARS_DIR , 1, 0777). intval($user_id). ".jpg";
			if (file_exists($avatar_file_path))	{
				unlink($avatar_file_path);
			}
		}
		return js_redirect("./?object=".$_GET["object"]._add_get());
	}

	//-----------------------------------------------------------------------------
	// Load user avatar to the server (multi-site)
	function _load_avatar () {
		$AVATAR = &$_FILES["avatar"];
		$MAX_IMAGE_SIZE	= &$this->AVATAR_MAX_FILE_SIZE;
		if (empty($AVATAR["size"]) || $AVATAR["size"] > $MAX_IMAGE_SIZE) {
			common()->_raise_error(t("Invalid image size"));
		}
		if (!in_array($AVATAR["type"], array("image/pjpeg", "image/jpeg"))) {
			common()->_raise_error(t("Invalid image type, JPEG only"));
		}
		// Check for errors and stop if exists
		if (common()->_error_exists()) {
			return false;
		}
		// Init dir class
		$DIR_OBJ = main()->init_class("dir", "classes/");
		$avatars_dir = $DIR_OBJ->_gen_dir_path($this->USER_ID, INCLUDE_PATH. SITE_AVATARS_DIR , 1, 0777);
		$avatar_file_path	= $avatars_dir. $this->USER_ID. ".jpg";
		// Do delete previous avatar (if existed one)
		if (file_exists($avatar_file_path)) {
			unlink($avatar_file_path);
		}
		// Upload original photo
		$move_result = move_uploaded_file($AVATAR["tmp_name"], $avatar_file_path);
		// Check if file uploaded successfully
		if (!$move_result || !file_exists($avatar_file_path) || !filesize($avatar_file_path) || !is_readable($avatar_file_path)) {
			common()->_raise_error("Uploading image error #001. Please <a href='".process_url("./?object=help&action=email_form")."'>contact</a> site admin.");
			trigger_error("Moving uploaded image error", E_USER_WARNING);
			return false;
		}
		// Second image type check (using GD)
		$real_image_info = getimagesize($avatar_file_path);
		if (empty($real_image_info) || !in_array($real_image_info["mime"], array("image/pjpeg", "image/jpeg")) || $real_image_info[2] != 2) {
			common()->_raise_error(t("Invalid image type, JPEG only"));
			trigger_error("Invalid image type, JPEG only", E_USER_WARNING);
			return false;
		}
		// Check for wrong photos that crashed GD (only if we do not have NETPBM)
		if (!defined("NETPBM_PATH") || NETPBM_PATH == "") {
			if (false === @imagecreatefromjpeg($avatar_file_path)) {
				common()->_raise_error("Uploading image error #002. Please <a href='".process_url("./?object=help&action=email_form")."'>contact</a> site admin.");
				trigger_error("Image that crashes GD found", E_USER_WARNING);
				unlink($avatar_file_path);
				return false;
			}
		}
		// Second image size checking (from the real file)
		if (!empty($MAX_IMAGE_SIZE) && filesize($avatar_file_path) > $MAX_IMAGE_SIZE) {
			common()->_raise_error(t("Invalid image size"));
			trigger_error("Image size hacking attempt", E_USER_WARNING);
			unlink($avatar_file_path);
			return false;
		}
		// Make thumbnail
		$resize_result = common()->make_thumb($avatar_file_path, $avatar_file_path, $this->AVATAR_MAX_HEIGHT, $this->AVATAR_MAX_WIDTH);
		// Check if file uploaded successfully
		if (!$resize_result || !file_exists($avatar_file_path) || !filesize($avatar_file_path)) {
			if (file_exists($avatar_file_path)) {
				unlink($avatar_file_path);
			}
			common()->_raise_error("Cant resize avatar. Error #001");
			return trigger_error("Cant resize avatar. Error #001", E_USER_WARNING);
		}
		// Check if avatar resized correctly, if not - then delete it
		if ($resize_result) {
			list($_width, $_height, , ) = @getimagesize($avatar_file_path);
			if ($_width > $this->AVATAR_MAX_WIDTH || $_height > $this->AVATAR_MAX_HEIGHT) {
				unlink($avatar_file_path);
				common()->_raise_error("Cant resize avatar. Error #002");
				return false;
			}
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Hook for the site_nav_bar module
	* 
	* @access
	* @param
	* @return
	*/
	function _nav_bar_items ($params = array()) {
		$OBJ = $params["nav_bar_obj"];
		if (!is_object($OBJ)) {
			return false;
		}
		$items = array();
		$items[]	= $OBJ->_nav_item("Home", "./");
		$items[]	= $OBJ->_nav_item("My Account", "./?object=account"._add_get());
		if ($_GET["action"] == "show") {
			$items[]	= $OBJ->_nav_item("Account Information");
		}
		return $items;
	}
}
