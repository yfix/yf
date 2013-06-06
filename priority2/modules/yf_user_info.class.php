<?php

//-----------------------------------------------------------------------------
// Class to handle user personal info
class yf_user_info {

	/** @var bool Additional JavaScript based form validation */
	var $_JS_VALIDATION = false;
	/** @var int User ID */
	var $USER_ID = null;
	/** @var int Avatar max width */
	var $AVATAR_MAX_WIDTH		= 100;
	/** @var int Avatar max height */
	var $AVATAR_MAX_HEIGHT		= 100;
	/** @var int Avatar max file size */
	var $AVATAR_MAX_FILE_SIZE	= 500000; // bytes

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Array of select boxes to process
		$this->_boxes = array(
			"state"			=> 'select_box("state",			$this->_states,			$selected, " ", 2, "", false)',
			"country"		=> 'select_box("country",		$this->_countries,		$selected, false, 1, "", false)',
			"status"		=> 'select_box("status",		$this->_statuses,		$selected, false, 2, "", false)',
			"sex"			=> 'select_box("sex",			$this->_sex,			$selected, false, 2, "", false)',
			"age"			=> 'select_box("age",			$this->_ages,			$selected, " ", 2, "", false)',
		);
		// Get user account type
		$this->_account_types	= main()->get_data("user_groups");
		$this->cur_account_type = strtolower($this->_account_types[$this->_user_info["group"]]);
		// Array of form fields to process
		$this->_text_fields	= array(
			"login",
			"email",
			"contact_by_email",
		);
		$this->_text_fields = array_merge($this->_text_fields, array(
			"user_name",
		));
		$this->_required_fields	= array(
			"user_name"
		);
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
		// Apply avatar limits
		if (defined("AVATAR_MAX_X")) {
			$this->AVATAR_MAX_WIDTH		= AVATAR_MAX_X;
		}
		if (defined("AVATAR_MAX_Y")) {
			$this->AVATAR_MAX_HEIGHT	= AVATAR_MAX_Y;
		}
		if (defined("MAX_IMAGE_SIZE")) {
			$this->AVATAR_MAX_FILE_SIZE	= MAX_IMAGE_SIZE;
		}
		// Array of dynamic info
	}

	//-----------------------------------------------------------------------------
	// This function handle user personal info
	function show () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Process correct steps
		$_REQUEST['step'] = intval($_REQUEST['step']);
		$step = $_REQUEST['step'] ? $_REQUEST['step'] : 1;
		if (in_array($step, range(1,2))) {
			$body = eval("return \$this->_step_".$step."();");
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Form to edit user info
	function _step_1 ($A = null) {
		if (empty($A)) {
			$A = $this->_user_info;
		}

		// Overwrite user info values with posted ones
		if (!empty($_POST)) {
			foreach ((array)$_POST as $k => $v) {
				if (isset($A[$k])) {
					$A[$k] = $v;
				}
			}
		}
		// Create JS array for required fields
		if ($this->_JS_VALIDATION && is_array($this->_required_fields)) {
			foreach ((array)$this->_required_fields as $v) {
				$v2.= "'".$v."', ";
			}
			foreach ((array)$this->_required_fields as $v) {
				$v3.= "'".t($v)."', ";	
			}
			$replace2["js_array_1"]	= substr($v2, 0, -2);
			$replace2["js_array_2"]	= substr($v3, 0, -2);
		}
		// Process user avatar
		$replace3 = array(
			"user_avatar"	=> _show_avatar($A["id"], $A, 0),
			"avatar_exists"	=> intval(_avatar_exists($A["id"])),
			"del_avatar_url"=> "./?object=".$_GET["object"]."&action=delete_avatar",
		);
		$avatar_block = tpl()->parse($_GET["object"]."/avatar_item", $replace3);
		// Create array to replace inside template
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"],
			"error_message"			=> _e(),
			"js_validator"			=> $this->_JS_VALIDATION ? tpl()->parse($_GET["object"]."/js_step2_".$this->cur_account_type, $replace2) : "",
			"js_form_code"			=> $this->_JS_VALIDATION ? " onsubmit=\"return form_check(this);\" " : "",
			"account_type"			=> $this->cur_account_type,
			"status"				=> $A["status"],
			"back"					=> tpl()->parse("system/back", $replace),
			"home"					=> tpl()->parse("system/home", array("home_link" => WEB_PATH)),
			"change_email_url"		=> "./?object=account&action=change_email",
			"avatar"				=> $avatar_block,
			"profile_url"			=> $this->_profile_url_form(),
			"avatar_max_height"		=> intval($this->AVATAR_MAX_HEIGHT),
			"avatar_max_width"		=> intval($this->AVATAR_MAX_WIDTH),
			"avatar_max_file_size"	=> intval($this->AVATAR_MAX_FILE_SIZE),
			"nick"					=> $A["nick"],
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $A[$item_name]);
		}
		// Fill all other form fields with values
		foreach ((array)$this->_text_fields as $name) {
			$replace[$name] = $A[$name];
		}
		$replace["user_name"] = $replace["agency_name"] = _display_name($A);
		$replace["recip_url"] = isset($this->_user_info["recip_url"]) ? $A["recip_url"] : "http://";

		// Dynamic info
		if (main()->USER_INFO_DYNAMIC) {
			$OBJ_DYNAMIC_INFO = &main()->init_class("dynamic_info", "classes/");
			$replace["dynamic_items"] = $OBJ_DYNAMIC_INFO->_edit($this->USER_ID);
		}
		
		// Parse template contents
		return tpl()->parse($_GET["object"]."/step_1_".$this->cur_account_type, $replace);
	}

	//-----------------------------------------------------------------------------
	// Profile URL form
	function _profile_url_form () {
		$replace = array(
			"profile_url"			=> $this->_user_info["profile_url"],
			"show_profile_url_form"	=> empty($this->_user_info["profile_url"]) ? 1 : 0,
		);
		return tpl()->parse($_GET["object"]."/profile_url", $replace);
	}

	//-----------------------------------------------------------------------------
	// Save user info
	function _step_2 () {
	
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		// Load user avatar
		if (!empty($_FILES["avatar"]["size"])) {
			$this->_load_avatar();
		}
		// Prepare phone and fax numbers
		if (isset($_POST["phone"])) {
			$_POST["phone"] = _prepare_phone($_POST["phone"]);
		}
		if (isset($_POST["fax"])) {
			$_POST["fax"] = _prepare_phone($_POST["fax"]);
		}
		// Validate form fields
		$this->_validate_form();
		// Check for errors
		if (common()->_error_exists()) {
			return $this->_step_1();
		}
/*		// Prepare values
		foreach ((array)$_POST as $k => $v) {
			$_POST[$k] = _es($v);
		}
*/		// Prepare SQL
		$sql_array = array(
			"nick"				=> $_POST["nick"],
			"has_avatar"		=> _avatar_exists($this->USER_ID) ? 1 : 0,
			"contact_by_email"	=> intval(!$_POST["contact_by_email"]),
		);
		// Process new password
		if (strlen($_POST["pswd"])) {
			$sql_array["password"] = $_POST["pswd"];
		}
		// Try to save user profile
		if (!empty($_POST["profile_url"])) {
			$sql_array["profile_url"] = $_POST["profile_url"];
		}
		// Do execute query
		update_user($this->_user_info['id'], $sql_array);

		if (main()->USER_INFO_DYNAMIC) {
			$OBJ_DYNAMIC_INFO = &main()->init_class("dynamic_info", "classes/");
			$replace["dynamic_items"] = $OBJ_DYNAMIC_INFO->_save($this->USER_ID);
		}

		// Last update
//		db()->_add_shutdown_query("UPDATE `".db('user')."` SET `last_update`=".time()." WHERE `id`=".intval($this->USER_ID));
		update_user($this->USER_ID, array("last_update" => time()));
		// Output cache trigger
		if (main()->OUTPUT_CACHING) {
			main()->call_class_method("output_cache", "classes/", "_exec_trigger", array(
				"user_id"	=> $this->USER_ID,
			));
		}
		// Update user stats
		main()->call_class_method("user_stats", "classes/", "_update", array("user_id" => $this->USER_ID));
		// Do update user info in session
		if (isset($_SESSION["user_info"])) {
			$_SESSION["user_info"] = "";
		}
		// Return user back
		return js_redirect("./?object=".$_GET["object"]._add_get());
	}	

	//-----------------------------------------------------------------------------
    // Form validator
	function _validate_form () {
		// Cleanup all $_POST fields
		foreach ((array)$_POST as $k => $v) {
			trim($_POST[$k]);
		}
		// Init default validator
		$VALIDATE_OBJ = main()->init_class("validate", "classes/");
		// Check user nick (required)
		$VALIDATE_OBJ->_check_user_nick($this->_user_info["nick"]);
		// Check profile url
		$VALIDATE_OBJ->_check_profile_url($this->_user_info["profile_url"]);
		// Special for the agency name
		if ($this->cur_account_type == "agency") {
			if ($_POST["name"] == "") {
				common()->_raise_error(t('Agency name required'));
			}
		}
		// Check other fields
		if ($_POST["measurements"] != "" && !preg_match('#[0-9]{2}([A-G]{0,4})-([0-9]{2})-([0-9]{2})#i', $_POST["measurements"])) {
			common()->_raise_error(t('Invalid measurements (example, 36DD-27-32)!'));
		}
		if ($_POST["recip_url"] == "http://" || $_POST["recip_url"] == "") {
			$_POST["recip_url"] = "";
		} elseif (!preg_match('#^http://[_a-z0-9-]+\.[_a-z0-9-]+#i', $_POST["recip_url"])) {
			common()->_raise_error(t('Invalid reciprocal URL'));
		}
	}

	//-----------------------------------------------------------------------------
	// Delete avatar from server
	function delete_avatar () {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		// Init dir class
		$DIR_OBJ = main()->init_class("dir", "classes/");
		// Process image
		$avatars_dir = $DIR_OBJ->_gen_dir_path($this->USER_ID, INCLUDE_PATH. SITE_AVATARS_DIR , 0, 0777);
		$avatar_path_small	= $avatars_dir. $this->USER_ID. ".jpg";
		$avatar_path_middle	= $avatars_dir. $this->USER_ID. "_m.jpg";
		if (file_exists($avatar_path_small)) {
			unlink($avatar_path_small);
		}
		if (file_exists($avatar_path_middle)) {
			unlink($avatar_path_middle);
		}
		// Update db record
		update_user($this->_user_info['id'], array(
			"has_avatar"	=> _avatar_exists($this->USER_ID) ? 1 : 0,
		));
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
			common()->_raise_error(t("Cant resize avatar"));
			return trigger_error("Cant resize avatar", E_USER_WARNING);
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
