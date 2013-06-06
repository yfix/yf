<?php

/**
* Gallery handler module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_gallery extends profy_module {

	/** @var array Array of current photo sizes details */
	var $PHOTO_TYPES = array(
		"ad thumbnail"	=> array(
			"max_x"		=> 50,
			"max_y"		=> 50,
			"sub_folder"=> "ad_thumbs/",
		),
		"thumbnail"	=> array(
			"max_x"		=> 100,
			"max_y"		=> 100,
			"sub_folder"=> "thumbs/",
		),
		"medium"	=> array(
			"max_x"		=> 350,
			"max_y"		=> 600,
			"sub_folder"=> "medium/",
		),
		"original"	=> array(
			"max_x"		=> 0,
			"max_y"		=> 0,
			"sub_folder"=> "original/",
		),
	);
	/** @var string */
	var $PHOTO_ITEM_DISPLAY_TYPE	= "thumbnail";
	/** @var string Root folder for the gallery photos */
	var $GALLERY_DIR				= "uploads/gallery/";
	/** @var string Photos names template. 
	* Any of these keys allowed:
	* {photo_id},{user_id},{folder_id},{photo_type}
	*/
	var $PHOTO_NAME_TEMPLATE		= "{user_id}_{photo_id}";
	/** @var string Default auto-generated images extension */
	var $IMAGE_EXT					= ".jpg";
	/** @var string Default name in form to use */
	var $PHOTO_NAME_IN_FORM			= "photo_file";
	/** @var int Max photo name length */
	var $MAX_NAME_LENGTH			= 100;
	/** @var int Max photo description length */
	var $MAX_DESC_LENGTH			= 500;
	/** @var int Max folder title length */
	var $MAX_FOLDER_TITLE_LENGTH	= 100;
	/** @var int Max folder comment length */
	var $MAX_FOLDER_COMMENT_LENGTH	= 500;
	/** @var int Limit uploaded image size in bytes */
	var $MAX_IMAGE_SIZE				= 500000;// bytes
	/** @var int Max number of photos to show in ads */
	var $MAX_PHOTOS_FOR_ADS			= 15;
	/** @var int Max number of photos to upload (0 - to unlimited) */
	var $MAX_TOTAL_PHOTOS			= 0;
	/** @var int Max number of folders (0 - to unlimited) */
	var $MAX_TOTAL_FOLDERS			= 0;
	/** @var int Max number of photos in folder (0 - to unlimited) */
// TODO: connect this
	var $MAX_PHOTOS_IN_FOLDER		= 0;
	/** @var int Number of photos per column to display */
	var $PHOTOS_IN_COLUMN			= 2;
	/** @var int Number of records to show on one page for "view all" */
	var $VIEW_ALL_ON_PAGE			= 50;
	/** @var int On stats page number of top galleries */
	var $STATS_TOP_ON_PAGE			= 10;
	/** @var int Number of items on the stats page */
	var $STATS_NUM_MOST_ACTIVE		= 25;
	/** @var int */
	var $STATS_NUM_LATEST			= 15;
	/** @var int */
// TODO: connect this
	var $STATS_NUM_MOST_COMMENTED	= 10;
	/** @var int */
// TODO: connect this
	var $STATS_NUM_MOST_VIEWED		= 10;
	/** @var bool Display or not groupped by user latest photos */
	var $LATEST_GROUP_BY_USER		= false;
	/** @var bool Make default photo from the first uploaded one automatically or not */
	var $MAKE_DEFAULT_PHOTO_AUTO	= true;
	/** @var int @conf_skip Default attributes for new directories */
	var $DEF_DIR_MODE				= 0777;
	/** @var bool All galleries search filter on/off */
	var $USE_FILTER					= 1;
	/** @var bool Skip not existed photos */
	var $SKIP_NOT_FOUND_PHOTOS		= 1;
	/** @var bool Get previous and next photos ids */
	var $GET_PREV_NEXT_PHOTOS		= 1;
	/** @var bool When deleting folder delete also all photos inside it, else photos will be assigned to the default folder */
	var $DELETE_FOLDER_WITH_PHOTOS	= 1;
	/** @var string Field name in session where entered passwords are stored */
	var $SESSION_PSWD_FIELD			= "gallery_pswds";
	/** @var bool Warn user about changing content level for the folder (photos will not be shown in public) */
	var $WARN_NON_PUBLIC_PHOTOS		= 1;
	/** @var int Display mini thumbs on medium size view (additional for the 
	* navigation for the next and prev photos).Set to "0" to disable 
	*/
	var $FOR_MEDIUM_NUM_MINI_THUMBS	= 9; 
	/** @var bool Display mini thumbs only from current folder */
	var $MINI_THUMBS_SAME_FOLDER	= false;
	/** @var bool Display mini thumbs for all availiable to display photos */
	var $MINI_THUMBS_SHOW_ALL		= false;
	/** @var bool Rating system for gallery on/off */
	var $ALLOW_RATE					= false;
	/** @var bool Geo filtering on/off */
	var $ALLOW_GEO_FILTERING		= false;
	/** @var bool Tagging system for gallery on/off */
	var $ALLOW_TAGGING				= true;
	/** @var int Max number of allowed tags for single photo */
	var $TAGS_PER_PHOTO				= 5;
	/** @var bool Crop, rotate image on/off */
	var $ALLOW_IMAGE_MANIPULATIONS	= true;
	/** @var bool If this turned on - then system will hide total ids for user, 
	* and wiil try to use small id numbers dedicated only for this user
	*/
	var $HIDE_TOTAL_ID				= false;
	/** @var int Number of items for the RSS feed */
	var $NUM_RSS 					= 10;
	/** @var array Available values for the user custom medium size */
	var $MEDIUM_SIZES				= array(
		350 => 350,
		450 => 450,
		600 => 600
	);
	/** @var array Thumb types */
	var $_thumb_types = array(
		0	=> "Original",
		1	=> "Square &#40;Cropped&#41;",
	);
	/** @var array Layout types */
	var $_layout_types = array(
		0	=> "Hierarchical &#40;show folders and featured photos&#41;",
		1	=> "Simple &#40;hide folders and show all photos&#41;",
	);
	/** @var array Thumbs location */
	var $_thumbs_loc = array(
		0	=> "Above the image",
		1	=> "Left side",
		2	=> "Right side",
	);
	/** @var array Slideshow modes */
	var $_slideshow_modes = array(
		0	=> "Default",
		1	=> "Magnifier pop-up",
		2	=> "Simple slideshow",
		3	=> "Dimmed slideshow",
	);
	/** @var array Thumbs in row */
	var $_thumbs_in_row = array(
		2, 3, 4, 10, 12, 15
	);
	/** @var array */
	var $DEFAULT_SETTINGS			= array();
	/** @var srting Default folder name */
	var $DEFAULT_FOLDER_NAME		= "General";
	/** @var bool What to show on user gallery home. (Currently availiable: latest|featured) */
	var $USER_GALLERY_HOME_SHOW		= "latest";
	/** @var bool allow delete comments */
	var $ALLOW_DELETE_COMMENTS		= true;
	/** @var bool Search comments posted by members */
	var $SEARCH_ONLY_MEMBER			= true;
	/** @var bool */
	var $USE_SQL_FORCE_KEY			= false;
	/** @var bool */
	var $ALLOW_BULK_UPLOAD			= false;
	/** @var array @conf_skip Params for the comments */
	var $_comments_params	= array(
		"return_action" => "show_medium_size",
	);

	/**
	* YF module constructor
	*/
	function _init () {
		// Gallery class name (to allow changing only in one place)
		define("GALLERY_CLASS_NAME", "gallery");
		// Sub modules folder
		define("GALLERY_MODULES_DIR", USER_MODULES_DIR. GALLERY_CLASS_NAME."/");
		// Set gallery dir
		if (defined("SITE_GALLERY_DIR")) {
			$this->GALLERY_DIR = SITE_GALLERY_DIR;
		}
		$this->DEFAULT_FOLDER_NAME = t($this->DEFAULT_FOLDER_NAME);
		// Array of select boxes to process
		$this->_boxes = array(
			"folder_id"			=> 'select_box("folder_id", 	$this->_folders_for_select, $selected, false, 2, "", false)',
			"privacy"			=> 'select_box("privacy",		$this->_privacy_types,	$selected, false, 2, "", false)',
			"allow_comments"	=> 'select_box("allow_comments",$this->_comments_types,	$selected, false, 2, "", false)',
			"content_level"		=> 'select_box("content_level",	$this->_content_levels,	$selected, false, 2, "", false)',
			"privacy2"			=> 'select_box("privacy",		$this->_privacy_types2,	$selected, false, 2, "", false)',
			"allow_comments2"	=> 'select_box("allow_comments",$this->_comments_types2,$selected, false, 2, "", false)',
			"show_in_ads"		=> 'check_box("show_in_ads", 	$this->_simple_switch,	$selected, false, 2, "", false)',
			"allow_rate"		=> 'check_box("allow_rate", 	$this->_simple_switch,	$selected, false, 2, "", false)',
			"allow_tagging"		=> 'check_box("allow_tagging", 	$this->_simple_switch,	$selected, false, 2, "", false)',
			"thumb_type"		=> 'select_box("thumb_type",	$this->_thumb_types,	$selected, false, 2, "", false)',
			"medium_size"		=> 'select_box("medium_size",	$this->MEDIUM_SIZES,	$selected, false, 2, "", false)',
			"layout_type"		=> 'select_box("layout_type",	$this->_layout_types,	$selected, false, 2, "", false)',
			"thumbs_loc"		=> 'select_box("thumbs_loc",	$this->_thumbs_loc,		$selected, false, 2, "", false)',
			"thumbs_in_row"		=> 'select_box("thumbs_in_row",	$this->_thumbs_in_row,	$selected, false, 2, "", false)',
			"slideshow_mode"	=> 'select_box("slideshow_mode",$this->_slideshow_modes,$selected, false, 2, "", false)',
			"is_featured"		=> 'check_box("is_featured", 	$this->_simple_switch,	$selected, false, 2, "", false)',
		);
		$this->_simple_switch = array(
			0 => t("No"),
			1 => t("Yes")
		);
		$this->_privacy_types	= main()->get_data("privacy_types");
		$this->_comments_types	= main()->get_data("allow_comments_types");
		$this->_content_levels	= main()->get_data("content_levels");
		// Prepare privacy and allow_comments for edit photos
		$this->_privacy_types2[0] = t("-- USE GLOBAL SETTINGS --");
		foreach ((array)$this->_privacy_types as $k => $v) {
			$this->_privacy_types2[$k] = $v;
		}
		$this->_comments_types2[0] = t("-- USE GLOBAL SETTINGS --");
		foreach ((array)$this->_comments_types as $k => $v) {
			$this->_comments_types2[$k] = $v;
		}
		// Do some translate
		$this->_thumb_types		= t($this->_thumb_types);
		$this->_layout_types	= t($this->_layout_types);
		$this->_thumbs_loc		= t($this->_thumbs_loc);
		$this->_slideshow_modes	= t($this->_slideshow_modes);
		// Reorder thumbs in a row
		$_tmp = array();
		foreach ((array)$this->_thumbs_in_row as $v) {
			$_tmp[$v] = $v;
		}
		$this->_thumbs_in_row = $_tmp;
		// Set number of photos for ads
		$this->MAX_PHOTOS_FOR_ADS = defined("MAX_PHOTOS_PER_AD") ? MAX_PHOTOS_PER_AD : 10;
		// Check total id mode
		$this->HIDE_TOTAL_ID = main()->HIDE_TOTAL_ID;
		if ($this->HIDE_TOTAL_ID && (
			MAIN_TYPE_ADMIN || 
			(empty($GLOBALS['HOSTING_ID']) && empty($this->USER_ID))
		)) {
			$this->HIDE_TOTAL_ID = false;
		}
		// Remove geo customization for the guests
		if (!$this->USER_ID) {
			$this->ALLOW_GEO_FILTERING = false;
		}
		// Tagging
		if (!is_object($this->TAG_OBJ && $this->ALLOW_TAGGING)) {
			$this->TAG_OBJ = main()->init_class("tags", "modules/");
		}
		// Check if we could handle bulk upload (using zip archive with photos)
		if ($this->ALLOW_BULK_UPLOAD && !file_exists(YF_PATH."classes/yf_pclzip.class.php")) {
			$this->ALLOW_BULK_UPLOAD = false;
		}
	}

	/**
	* Default method
	*/
	function show () {
		// Short call for the user's gallery
		if (!empty($_GET["id"])) {
			$_GET["action"] = "show_gallery";
			return $this->show_gallery();
		}
		return $this->_show_stats();
	}

	/**
	* Edit gallery settings
	*/
	function settings () {
		$OBJ = $this->_load_sub_module("gallery_settings");
		return is_object($OBJ) ? $OBJ->_edit() : "";
	}

	/**
	* Get gallery settings
	*/
	function _get_settings ($user_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_settings");
		return is_object($OBJ) ? $OBJ->_get($user_id) : "";
	}

	/**
	* Create default gallery settings for the given user ID
	*/
	function _start_settings ($user_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_settings");
		return is_object($OBJ) ? $OBJ->_start ($user_id) : "";
	}

	/**
	* View user photos
	*/
	function view () {
		return $this->show_gallery();
	}

	/**
	* Edit gallery
	*/
	function edit () {
		return $this->show_gallery("edit_");
	}

	/**
	* Show user gallery
	*/
	function show_gallery ($stpl_prefix = "show_") {
		$_GET["id"] = intval($_GET["id"]);
		if ($this->HIDE_TOTAL_ID && $GLOBALS['HOSTING_ID']) {
			$user_id = $GLOBALS['HOSTING_ID'];
		} else {
			$user_id = !empty($_GET["id"]) ? $_GET["id"] : $this->USER_ID;
		}
		// Try to get given user info
		if (!empty($user_id)) {
			$user_info = user($user_id, "", array("WHERE" => array("active" => "1")));
		}
		if (empty($user_info)) {
			return _e(t("No such user in database!"));
		}
		if (empty($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $user_info;
		}
		if (MAIN_TYPE_USER) {
			$this->is_own_gallery = intval(($_GET["id"] && $this->USER_ID == $_GET["id"]) || (!$_GET["id"] && !empty($this->USER_ID)));
		} elseif (MAIN_TYPE_ADMIN) {
			$this->is_own_gallery = true;
		}
		if ($this->is_own_gallery) {
			$this->SKIP_NOT_FOUND_PHOTOS = false;
		}
		// Check if user already have started gallery
		$num_user_photos = db()->query_num_rows("SELECT `id` FROM `".db('gallery_photos')."` WHERE `user_id`=".intval($user_id));
		if (!empty($user_id) && empty($num_user_photos)) {
			$replace = array(
				"is_logged_in"	=> intval((bool) $this->USER_ID),
				"is_own_gallery"=> $this->is_own_gallery,
				"start_link"	=> "./?object=".GALLERY_CLASS_NAME."&action=add_photo"._add_get(array("page")),
				"user_id"		=> intval($this->USER_ID),
			);
			$body = tpl()->parse(GALLERY_CLASS_NAME."/no_gallery_yet", $replace);
		} else {
			$body = $this->_show_user_photos($user_info, 0, $stpl_prefix);
		}
		return $body;
	}

	/**
	* View folder contents
	*/
	function view_folder () {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_view_folder() : "";
	}

	/**
	* Alias for the "view_folder"
	*/
	function folder () {
		$_GET["action"] = "view_folder";
		return $this->view_folder();
	}

	/**
	* Add new folder
	*/
	function add_folder () {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_add_folder() : "";
	}

	/**
	* Edit folder
	*/
	function edit_folder () {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_edit_folder() : "";
	}

	/**
	* Delete folder
	*/
	function delete_folder () {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_delete_folder() : "";
	}

	/**
	* Display search form (alias for the "show_all_galleries")
	*/
	function search () {
		return $this->show_all_galleries();
	}

	/**
	* Show list of all galleries
	*/
	function show_all_galleries () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		$OBJ = $this->_load_sub_module("gallery_stats");
		return is_object($OBJ) ? $OBJ->_show_all_galleries() : "";
	}

	/**
	* Display medium size photo
	*/
	function show_medium_size () {
		return $this->_show_single_photo(array(
			"template_name"	=> GALLERY_CLASS_NAME."/show_medium_size",
			"photo_type"	=> "medium",
		));
	}

	/**
	* Alias for the "show_medium_size"
	*/
	function medium () {
		$_GET["action"] = "show_medium_size";
		return $this->show_medium_size();
	}

	/**
	* Display full size photo
	*/
	function show_full_size () {
		return $this->_show_single_photo(array(
			"template_name"	=> GALLERY_CLASS_NAME."/show_full_size",
			"photo_type"	=> "original",
		));
	}

	/**
	* Alias for the "show_full_size"
	*/
	function full () {
		$_GET["action"] = "show_full_size";
		return $this->show_full_size();
	}

	/**
	* Display user photos
	*/
	function _show_user_photos ($user_info = array(), $single_folder_id = 0, $stpl_prefix = "show_") {
		$OBJ = $this->_load_sub_module("gallery_show_photos");
		return is_object($OBJ) ? $OBJ->_show_user_photos ($user_info, $single_folder_id, $stpl_prefix) : "";
	}

	/**
	* Show photo item
	*/
	function _show_photo_item ($photo_info = array(), $stpl_prefix = "show_") {
		$OBJ = $this->_load_sub_module("gallery_show_photos");
		return is_object($OBJ) ? $OBJ->_show_photo_item ($photo_info, $stpl_prefix) : "";
	}

	/**
	* Show random photo
	*/
	function _show_random_photo ($photo_info = array(), $stpl_prefix = "widget_") {
		$OBJ = $this->_load_sub_module("gallery_show_photos");
		return is_object($OBJ) ? $OBJ->_show_photo_item ($photo_info, $stpl_prefix) : "";
	}

	/**
	* Galleries stats
	*/
	function _show_stats ($MAIN_STPL = "", $ITEM_STPL = "", $NUM_ITEM = 0) {
		$OBJ = $this->_load_sub_module("gallery_stats");
		return is_object($OBJ) ? $OBJ->_show_stats($MAIN_STPL, $ITEM_STPL, $NUM_ITEM) : "";
	}

	/**
	* Display single photo
	*/
	function _show_single_photo ($params = array()) {
		$OBJ = $this->_load_sub_module("gallery_show_photos");
		return is_object($OBJ) ? $OBJ->_show_single_photo($params) : "";
	}

	/**
	* Add new photo method
	*/
	function add_photo () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_add_photo() : "";
	}

	/**
	* Edit photo management
	*/
	function edit_photo () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_edit_photo() : "";
	}

	/**
	* Do delete photo
	*/
	function delete_photo () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_delete_photo() : "";
	}

	/**
	* Image cropper
	*/
	function crop_photo () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_crop_photo() : "";
	}

	/**
	* Image rotater
	*/
	function rotate_photo () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_rotate_photo() : "";
	}

	/**
	* Change photo sorting position
	*/
	function sort_photo () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_sort_photo() : "";
	}

	/**
	* Change password for private gallery of given user
	*/
	function _enter_pswd ($FOLDER_ID = 0) {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_enter_pswd($FOLDER_ID) : "";
	}

	/**
	* Change show in ads status
	*/
	function change_show_ads () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_change_show_ads() : "";
	}

	/**
	* Change allow_rate status
	*/
	function change_rate_allowed () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_change_rate_allowed() : "";
	}

	/**
	* Change allow_tagging status
	*/
	function change_tagging_allowed () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_change_tagging_allowed() : "";
	}

	/**
	* Make given photo default
	*/
	function make_default () {
		$OBJ = $this->_load_sub_module("gallery_manage");
		return is_object($OBJ) ? $OBJ->_make_default() : "";
	}

	/**
	* Manage tags for the selected photo
	*/
	function edit_tags ($photo_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_tags");
		return is_object($OBJ) ? $OBJ->_edit_tags($photo_id) : "";
	}

	/**
	* Manage tags for the selected photo
	*/
	function edit_tags_popup () {
		main()->NO_GRAPHICS = true;
		echo common()->show_empty_page($this->edit_tags());
	}

	/**
	* Prefetch tags for given ids
	*/
	function _get_tags ($photos_ids = array()) {
		$OBJ = $this->_load_sub_module("gallery_tags");
		return is_object($OBJ) ? $OBJ->_get_tags($photos_ids) : "";
	}

	/**
	* Return photo name (using name template)
	*/
	function _create_name_from_tpl ($photo_info = array(), $cur_photo_type = "original", $return_full_path = 1) {
		$type	= $this->PHOTO_TYPES[$cur_photo_type];
		// Prepare replace pairs
		$name_replace = array(
			"photo_id"		=> intval($photo_info["id"]),
			"id2"			=> intval($photo_info["id2"]),
			"user_id"		=> intval($photo_info["user_id"]),
			"folder_id"		=> intval($photo_info["folder_id"]),
			"photo_type"	=> $cur_photo_type,
		);
		$photo_name = $this->PHOTO_NAME_TEMPLATE;
		// Replace given items (if exists ones)
		foreach ((array)$name_replace as $item => $value) {
			$photo_name = str_replace("{".$item."}", $value, $photo_name);
		}
		// Create full path to photo (you can just add WEB_PATH, REAL_PATH or INCLUDE_PATH to it then)
		if ($return_full_path) {
			$photo_name = $this->GALLERY_DIR. $type["sub_folder"]. _gen_dir_path($photo_info["user_id"], "", 0, $this->DEF_DIR_MODE). $photo_name. $this->IMAGE_EXT;
		}
		return $photo_name;
	}

	/**
	* Return web path to given photo
	*/
	function _photo_web_path ($photo_info = array(), $cur_photo_type = "original") {
		$type	= $this->PHOTO_TYPES[$cur_photo_type];
		if ($this->HIDE_TOTAL_ID && $GLOBALS["HOSTING_FULL_NAME"]) {
			$photo_name = $this->GALLERY_DIR. $type["sub_folder"]. $photo_info["id2"]. $this->IMAGE_EXT;
			return "http://".$GLOBALS["HOSTING_FULL_NAME"]."/".$photo_name;
		}
		$photo_name = $this->_create_name_from_tpl($photo_info, $cur_photo_type, 1);
		return WEB_PATH. $photo_name;
	}

	/**
	* Return filesystem path to given photo
	*/
	function _photo_fs_path ($photo_info = array(), $cur_photo_type = "original") {
		$type	= $this->PHOTO_TYPES[$cur_photo_type];
		if ($this->HIDE_TOTAL_ID && $GLOBALS["HOSTING_FULL_NAME"]) {
			$photo_name = $this->GALLERY_DIR. $type["sub_folder"]. $photo_info["id2"]. $this->IMAGE_EXT;
			return INCLUDE_PATH."users/".$GLOBALS["HOSTING_FULL_NAME"]."/".$photo_name;
		}
		$photo_name = $this->_create_name_from_tpl($photo_info, $cur_photo_type, 1);
		return INCLUDE_PATH. $photo_name;
	}

	/**
	* Reverse operation for the "_create_name_from_tpl" method
	*/
	function _get_info_from_file_name ($file_name = "") {
		list($user_id, $photo_id) = explode("_", str_replace($this->IMAGE_EXT, "", $file_name));
		$info = array(
			"user_id"	=> $user_id,
			"photo_id"	=> $photo_id,
		);
		return $info;
	}

	/**
	* Get real photo sizes from photo files for the given photo db record
	*/
	function _update_other_info ($photo_info = array()) {
		$OBJ = $this->_load_sub_module("gallery_utils");
		return $OBJ->_update_other_info($photo_info);
	}

	/**
	* Do filter text from unwanted sequences of symbols
	*/
	function _filter_text ($body) {
		$body = preg_replace("/([^\s]+)\r\n/i", "\$1 \r\n", $body);
		$body = _check_words_length($body);
		return $body;
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Try to load sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ = &main()->init_class($module_name, GALLERY_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("GALLERY: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}

	/**
	* Get user's available folders
	*/
	function _get_user_folders ($user_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_get_user_folders($user_id) : "";
	}

	/**
	* Get users available folders (for many users at one time)
	*/
	function _get_user_folders_for_ids ($users_ids = array()) {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_get_user_folders_for_ids($users_ids) : "";
	}

	/**
	* Fix default folder id
	*/
	function _fix_and_get_folder_id ($photo_info = array()) {
		$user_id = $photo_info["user_id"];
		if (empty($photo_info) || empty($user_id)) {
			return false;
		}
		$FOLDER_ID = $photo_info["folder_id"];
		// Get user folders
		$user_folders = $this->_get_user_folders($user_id);
		// Get default folder id
		$def_folder_id = $this->_get_def_folder_id($user_folders);
		// Do set default folder for photo with empty folder field
		if (empty($FOLDER_ID) && !empty($def_folder_id)) {
			// Do update record
			db()->UPDATE("gallery_photos", array(
				"folder_id"	=> intval($def_folder_id),
			), "`id`=".intval($photo_info["id"]));
			$FOLDER_ID = $def_folder_id;
		}
		return $FOLDER_ID;
	}

	/**
	* Get default folder from given user folders array
	*/
	function _get_def_folder_id ($user_folders = array()) {
		$OBJ = $this->_load_sub_module("gallery_folders");
		return is_object($OBJ) ? $OBJ->_get_def_folder_id($user_folders) : "";
	}

	/**
	* Get max privacy value that current user can view
	*/
	function _get_max_privacy ($user_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_utils");
		return $OBJ->_get_max_privacy($user_id);
	}

	/**
	* Check privacy permissions (allow current user to view or not)
	*/
	function _privacy_check ($folder_privacy = 0, $photo_privacy = 0, $owner_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_utils");
		return $OBJ->_privacy_check($folder_privacy, $photo_privacy, $owner_id);
	}

	/**
	* Check allow comments (allow current user to view/post or not)
	*/
	function _comment_allowed_check ($folder_comments = 0, $photo_comments = 0, $owner_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_utils");
		return $OBJ->_comment_allowed_check($folder_comments, $photo_comments, $owner_id);
	}

	/**
	* Check if post comment is allowed
	*
	* @access	private
	* @return	bool
	*/
	function _comment_is_allowed ($params = array()) {
		$photo_info	= $this->_photo_info;
		$FOLDER_ID	= $photo_info["folder_id"];
		if (empty($FOLDER_ID)) {
			return true;
		}
		$cur_folder_info = $this->_user_folders_infos[$FOLDER_ID];
		if ($_GET["action"] == "show_medium_size") {
			// Check if target user is ignored by owner
			if (common()->_is_ignored($this->USER_ID, $photo_info["user_id"])) {
				return false;
			}
			return $this->_comment_allowed_check($cur_folder_info["allow_comments"], $photo_info["allow_comments"], $photo_info["user_id"]);
		}
		return true;
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql ($_source_sql = "") {
		$OBJ = $this->_load_sub_module("gallery_filter");
		return is_object($OBJ) ? $OBJ->_create_filter_sql($_source_sql) : "";
	}

	/**
	* Session - based filter form
	*/
	function _show_filter () {
		$OBJ = $this->_load_sub_module("gallery_filter");
		return is_object($OBJ) ? $OBJ->_show_filter() : "";
	}

	/**
	* Filter save method
	*/
	function save_filter ($silent = false) {
		$OBJ = $this->_load_sub_module("gallery_filter");
		return is_object($OBJ) ? $OBJ->_save_filter($silent) : "";
	}

	/**
	* Clear filter
	*/
	function clear_filter ($silent = false) {
		$OBJ = $this->_load_sub_module("gallery_filter");
		return is_object($OBJ) ? $OBJ->_clear_filter($silent) : "";
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$NAV_BAR_OBJ = &$params["nav_bar_obj"];
		if (!is_object($NAV_BAR_OBJ)) {
			return false;
		}
		// Save old items
		$old_items = $params["items"];
		// Create new items
		$items = array();
		$items[]	= $NAV_BAR_OBJ->_nav_item("Home", "./");
		$items[]	= $NAV_BAR_OBJ->_nav_item("Galleries", "./?object=".GALLERY_CLASS_NAME);
		if (!in_array($_GET["action"], array("show", "show_all_galleries"))) {
			if (!empty($this->_author_name)) {
				$items[]	= $NAV_BAR_OBJ->_nav_item(_prepare_html($this->_author_name), "./?object=".GALLERY_CLASS_NAME."&action=show_gallery&id=".$this->_author_id);
			} elseif (!empty($this->USER_ID)) {
				$items[]	= $NAV_BAR_OBJ->_nav_item(_prepare_html(_display_name($this->_user_info)), "./?object=".GALLERY_CLASS_NAME."&action=show_gallery&id=".$this->USER_ID);
			}
		}
		if (in_array($_GET["action"], array("show_all_galleries"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Galleries Search");
		} elseif (in_array($_GET["action"], array("show_gallery"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("View Gallery");
		} elseif (in_array($_GET["action"], array("show_medium_size"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("View Medium Size");
		} elseif (in_array($_GET["action"], array("add_photo"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Add New Photo");
		} elseif (in_array($_GET["action"], array("edit_photo"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Edit Photo");
		} elseif (in_array($_GET["action"], array("add_folder"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Add New Folder");
		} elseif (in_array($_GET["action"], array("edit_folder"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Edit Folder");
		} elseif (in_array($_GET["action"], array("view_folder"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("View Folder");
		}
		return $items;
	}

	/**
	* Clean up gallery
	*/
	function _cleanup () {
		$OBJ = $this->_load_sub_module("gallery_cleanup");
		return is_object($OBJ) ? $OBJ->_cleanup() : "";
	}

	/**
	* Sync public photos
	*/
	function _sync_public_photos ($user_id = 0) {
		$OBJ = $this->_load_sub_module("gallery_utils");
		return $OBJ->_sync_public_photos($user_id);
	}

	/**
	* Display single photo link (Specially for AJAX)
	*/
	function compact_view () {
		$OBJ = $this->_load_sub_module("gallery_compact");
		return is_object($OBJ) ? $OBJ->_compact_view() : "";
	}

	/**
	* Display latest photos by pages
	*/
	function latest () {
		$OBJ = $this->_load_sub_module("gallery_stats");
		return is_object($OBJ) ? $OBJ->_show_latest() : "";
	}

	/**
	* Display latest photos by pages using geo filter
	*/
	function latest_geo () {
		if (!$this->ALLOW_GEO_FILTERING) {
			return false;
		}
		$OBJ = $this->_load_sub_module("gallery_stats");
		return is_object($OBJ) ? $OBJ->_show_latest(array("geo" => 1)) : "";
	}

	/**
	* Display AJAX block for photo rate
	*/
	function _show_rate_block ($photo_info = array()) {
		if (!$this->ALLOW_RATE || empty($photo_info) || empty($photo_info["allow_rate"])) {
			return "";
		}
		$params = array(
			"photo_id"	=> $photo_info["id"],
			"user_id"	=> $photo_info["user_id"],
			"rating"	=> $photo_info["rating"],
			"num_votes"	=> $photo_info["num_votes"],
		);
		$OBJ = main()->init_class("photo_rating");
		return is_object($OBJ) ? $OBJ->_show_ajax_box($params) : "";
	}

	/**
	* Display error message
	*/
	function _error_msg ($type = "ban_images") {
		$error_msg = "";
		if ($type == "ban_images") {
			$error_msg = t("You broke some of our rules, so you are not allowed to manage photos!<br />For more details <a href=\"@url\">click here</a>", array("@url" => process_url("./?object=faq&action=view&id=16")));
		}
		return _e($error_msg);
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($SITE_MAP_OBJ = false) {
		$OBJ = $this->_load_sub_module("gallery_utils");
		return $OBJ->_site_map_items($SITE_MAP_OBJ);
	}

	/**
	* Admin action: delete all tags for the selected user
	*/
	function del_tags_by_user () {
		if (MAIN_TYPE_USER) {
			return false;
		}
// TODO: HIDE_TOTAL_ID
		$user_id = intval($_GET["id"]);
		if (empty($user_id)) {
			return "No user id!";
		}
		db()->query("DELETE FROM `".db('tags')."` WHERE `user_id`=".intval($user_id));
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}
	
	/**
	* Hook for user profile
	*/
	function _for_user_profile($user_id, $MAX_SHOW_GALLERY_PHOTO){
		$OBJ = $this->_load_sub_module("gallery_integration");
		return $OBJ->_for_user_profile($user_id, $MAX_SHOW_GALLERY_PHOTO);
	}

	/**
	* Show For home page
	*/
	function _for_home_page($num = 5) {
		$OBJ = $this->_load_sub_module("gallery_integration");
		return $OBJ->_for_home_page($num);
	}
	
	/**
	* Comments to photos search
	*/
	function search_comments() {
		$OBJ = $this->_load_sub_module("gallery_search_comments");
		return $OBJ->search_comments();
	}
	
	/**
	* Do delete comment to photo
	*/
	function delete_gallery_comment() {
		$OBJ = $this->_load_sub_module("gallery_search_comments");
		return $OBJ->_delete();
	}
	
	/**
	* Last photo
	*/
	function _widget_last_photo ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1, "cache_ttl" => 600);
		}
		$OBJ = $this->_load_sub_module("gallery_integration");
		return $OBJ->_widget_last_photo($num);

	}

	/**
	* Cloud of tags for gallery
	*/
	function _widget_tags_cloud ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1, "cache_ttl" => 600);
		}
		$OBJ = main()->init_class("tags");
		$items = $OBJ->_tags_cloud("gallery");
		if (!$items) {
			return "";
		}
		$replace = array(
			"items" => $items,
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/widget_cloud", $replace);
	}

	/**
	* User folders
	*/
	function _widget_user_folders ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0, "object" => "gallery");
		}
// TODO: show for gallery owner
		$_info = $this->_get_user_folders($this->USER_ID);
		if (!$_info) {
			return "";
		}
		$replace = array(
			"info"	=> $_info,
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/widget_folders", $replace);
	}

	/**
	* Random photo
	*/
	function _widget_random_photo ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0);
		}
		// Try to get given photo info
		$_photo_info = db()->query_fetch(
			"SELECT r1.* 
			FROM `".db('gallery_photos')."` AS r1 
			JOIN ( 
				SELECT (RAND() * ( 
					SELECT MAX(`id`) FROM `".db('gallery_photos')."` 
				)) AS id 
			) AS r2 
			WHERE r1.id >= r2.id 
				AND r1.`is_public` = '1' 
				AND r1.`active` = '1' 
			ORDER BY r1.id ASC 
			LIMIT 1"
		);
		if (!$_photo_info) {
			return "";
		}
		return $this->_show_random_photo($_photo_info);
	}
	
	function _rss_general(){
		$OBJ = $this->_load_sub_module("gallery_integration");
		return $OBJ->_rss_general();
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Gallery settings",
				"url" 	=> "./?object=gallery&action=settings",
			),
			array(
				"name"	=> "View current content",
				"url" 	=> "./?object=gallery",
			),
			array(
				"name"	=> "Add photos",
				"url" 	=> "./?object=gallery&action=add_photo",
			),
			array(
				"name"	=> "Create a new folder",
				"url" 	=> "./?object=gallery&action=add_folder",
			),
			array(
				"name"	=> "Comments to my gallery",
				"url" 	=> "./?object=gallery&action=search_comments",
			),
		);
		return $menu;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show_gallery"		=> "",
			"add_folder"		=> "Create folder",
			"search_comments"	=> "Comments to my gallery",
			"view_folder"		=> "",
			"show_medium_size"	=> _prepare_html($this->_folder_name." : ".(strlen($this->_photo_info["name"]) ? $this->_photo_info["name"] : $this->_author_name." ".$this->_photo_info["id2"])),
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $page_header ? _prepare_html($page_header) : t("Gallery"),
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
