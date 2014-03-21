<?php

/**
* Display photos in different manner
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_show_photos {

	/**
	* Display single photo
	*/
	function _show_single_photo ($params = array()) {
		$_GET["id"] = intval($_GET["id"]);
		// Prepare params
		$TEMPLATE_NAME	= !empty($params["template_name"]) ? $params["template_name"] : 'gallery'."/show_medium_size";
		$PHOTO_TYPE		= !empty($params["photo_type"]) ? $params["photo_type"] : "medium";
		// Try to get given post info
		$sql = "SELECT * FROM ".db('gallery_photos')." WHERE ";
		if (module('gallery')->HIDE_TOTAL_ID) {
			$sql .= "id2=".intval($_GET["id"])." AND user_id=".intval($GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : main()->USER_ID);
		} else {
			$sql .= "id=".intval($_GET["id"]);
		}
		module('gallery')->_photo_info = db()->query_fetch($sql);
		$photo_info = &module('gallery')->_photo_info;
		if (empty(module('gallery')->_photo_info["id"])) {
			return _e("No such photo!");
		}
		// Try to get given user info
		$user_id = module('gallery')->_photo_info["user_id"];
		if ($user_id) {
			$user_info = user($user_id, "", array("WHERE" => array("active" => "1")));
		}
		if (empty($user_info)) {
			return _e("No such user in database!");
		}
		if (empty($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $user_info;
		}
		if (MAIN_TYPE_USER) {
			module('gallery')->is_own_gallery = intval(!empty(main()->USER_ID) && main()->USER_ID == module('gallery')->_photo_info["user_id"]);
		} elseif (MAIN_TYPE_ADMIN) {
			module('gallery')->is_own_gallery = true;
		}
		// Get user gallery settings
		module('gallery')->CUR_SETTINGS = module('gallery')->_get_settings($user_id);
		$settings = module('gallery')->CUR_SETTINGS;
		// Get available user folders
		if (empty(module('gallery')->_user_folders_infos)) {
			module('gallery')->_user_folders_infos = module('gallery')->_get_user_folders($user_info["id"]);
		}
		// Prepare folder info
		$FOLDER_ID = module('gallery')->_photo_info["folder_id"];
		if (empty($FOLDER_ID)) {
			module('gallery')->_fix_and_get_folder_id(module('gallery')->_photo_info);
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = module('gallery')->_user_folders_infos[$FOLDER_ID];
			$this->_cur_folder_info = $cur_folder_info;
		}
		// Folder info is REQUIRED here
		if (empty($cur_folder_info)) {
			return _e("Folder info is required");
		}
		// Prepare global vars
		module('gallery')->_author_id		= intval($user_info["id"]);
		module('gallery')->_author_name	= _display_name($user_info);
		module('gallery')->_folder_name	= _prepare_html($cur_folder_info["title"]);
		// ###########################
		// Access checks
		// ###########################
		if (!module('gallery')->is_own_gallery) {
			// Check privacy permissions
			if (!module('gallery')->_privacy_check($cur_folder_info["privacy"], module('gallery')->_photo_info["privacy"], $user_info["id"])) {
				return _e("You are not allowed to view this gallery folder");
			}
			// Check for password for protected gallery
			if (!empty($cur_folder_info["password"])) {
				$PASSWORD_MATCHED = $_SESSION[module('gallery')->SESSION_PSWD_FIELD][$FOLDER_ID] == $cur_folder_info["password"];
			}
			// Display form to enter the password
			if (!empty($cur_folder_info["password"]) && !$PASSWORD_MATCHED && MAIN_TYPE_USER) {
				return module('gallery')->_enter_pswd($FOLDER_ID);
			}
		}
		// Comments block check
		$comments_allowed = module('gallery')->_comment_allowed_check ($cur_folder_info["allow_comments"], module('gallery')->_photo_info["allow_comments"], module('gallery')->_photo_info["user_id"]);
		// Prepare links to the adjacent photos
		list($prev_photo_link, $next_photo_link) = $this->_get_prev_next_links($user_info, $cur_folder_info["id"]);
		// Mini-thumbs display
		$mini_thumbs = "";
		if ($PHOTO_TYPE == "medium") {
			$mini_thumbs = $this->_show_mini_thumbs($user_info, array(
				"prev_photo_link"	=> $settings["thumbs_loc"] == 0 ? $prev_photo_link : "",
				"next_photo_link"	=> $settings["thumbs_loc"] == 0 ? $next_photo_link : "",
			));
			if (module('gallery')->MINI_THUMBS_SHOW_ALL) {
				$prev_photo_link = "";
				$next_photo_link = "";
			}
		}
		// Prepare show in ads
		$SHOW_IN_ADS_ALLOWED = 0;
		if ($cur_folder_info["content_level"] <= 1 && $cur_folder_info["privacy"] <= 1 && $cur_folder_info["password"] == "") {
			$SHOW_IN_ADS_ALLOWED = 1;
		}

		$this->_tags = module('gallery')->_show_tags($photo_info["id"]);

		// Prepare web paths to photo
		$img_web_path = module('gallery')->_photo_web_path($photo_info, $PHOTO_TYPE);
		$full_img_web_path = module('gallery')->_photo_web_path($photo_info, "original");
		// Photo id for outside
		$_web_photo_id	= $_GET["id"];
		if (module('gallery')->HIDE_TOTAL_ID) {
			$_web_photo_id = $photo_info["id2"];
		}
		// Thumbs location helper
		$_thumbs_loc = array(
			0 => "top",
			1 => "left",
			2 => "right",
		);
		// Process template
		$replace = array(
			"is_logged_in"			=> intval((bool) main()->USER_ID),
			"is_own_gallery"		=> module('gallery')->is_own_gallery,
			"user_name"				=> module('gallery')->_author_name,
			"user_profile_link"		=> _profile_link($user_info["id"]),
			"user_gallery_link"		=> "./?object=".'gallery'."&action=show_gallery".(module('gallery')->HIDE_TOTAL_ID ? "" : "&id=".$user_info["id"]). _add_get(array("page")),
			"user_folder_link"		=> $FOLDER_ID ? "./?object=".'gallery'."&action=view_folder&id=".(module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $FOLDER_ID). _add_get(array("page")) : "",
			"large_size_link"		=> "./?object=".'gallery'."&action=show_full_size&id=".$_web_photo_id. _add_get(array("page")),
			"img_src"				=> $img_web_path,
			"full_image_link"		=> $full_img_web_path,
			"photo_name"			=> _prepare_html(strlen(module('gallery')->_photo_info["name"]) ? module('gallery')->_photo_info["name"] : "photo"),
			"photo_desc"			=> _prepare_html(module('gallery')->_photo_info["desc"]),
			"cat_name"				=> module('gallery')->_cat_name,
			"page_link"				=> process_url("./?object=".'gallery'."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page"))),
			"user_id"				=> intval(main()->USER_ID),
			"comments"				=> $settings["allow_comments"] != 9 ? module('gallery')->_view_comments(array("object_id" => $photo_info["id"])) : "",
			"prev_photo_link"		=> $prev_photo_link,
			"next_photo_link"		=> $next_photo_link,
			"mini_thumbs"			=> $mini_thumbs,
			"edit_photo_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=edit_photo&id=".$_web_photo_id. _add_get(array("page")) : "",
			"delete_photo_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=delete_photo&id=".$_web_photo_id. _add_get(array("page")) : "",
			"change_show_ads_link"	=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=change_show_ads&id=".$_web_photo_id. _add_get(array("page")) : "",
			"make_default_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=make_default&id=".$_web_photo_id. _add_get(array("page")) : "",
			"show_in_ads"			=> $SHOW_IN_ADS_ALLOWED && module('gallery')->is_own_gallery ? intval((bool) $photo_info["show_in_ads"]) : -1,
			"rate_enabled"			=> intval((bool) module('gallery')->ALLOW_RATE),
			"rate_allowed"			=> module('gallery')->ALLOW_RATE ? intval((bool) $photo_info["allow_rate"]) : 0,
			"rating"				=> $photo_info["allow_rate"] ? round($photo_info["rating"], 1) : "",
			"rate_num_votes"		=> $photo_info["allow_rate"] ? intval($photo_info["num_votes"]) : "",
			"rate_last_voted"		=> $photo_info["allow_rate"] ? _format_date($photo_info["last_vote_date"]) : "",
			"rate_block"			=> $photo_info["allow_rate"] ? module('gallery')->_show_rate_block($photo_info) : "",
			"change_rate_link"		=> module('gallery')->ALLOW_RATE && module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=change_rate_allowed&id=".$_web_photo_id. _add_get(array("page")) : "",
			"tagging_enabled"		=> intval((bool) module('gallery')->ALLOW_TAGGING),
			"tagging_allowed"		=> module('gallery')->ALLOW_TAGGING ? intval((bool) $photo_info["allow_tagging"]) : 0,
			"change_tagging_link"	=> module('gallery')->ALLOW_TAGGING && module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=change_tagging_allowed&id=".$_web_photo_id. _add_get(array("page")) : "",
			"tags"					=> module('gallery')->ALLOW_TAGGING && !empty($tags) ? $tags : "",
			"allow_edit_tag"		=> $allow_edit_tags ? 1 : 0,
			"edit_tag_link"			=> $allow_edit_tags ? process_url("./?object=".'gallery'."&action=edit_tags_popup&id=".$_web_photo_id. _add_get(array("page"))) : "",
			"tags_block"			=> module('gallery')->ALLOW_TAGGING ? $this->_tags[$photo_info["id"]] : "",
			"thumbs_location"		=> $_thumbs_loc[$settings["thumbs_loc"]],
			"thumbs_in_row"			=> intval($settings["thumbs_in_row"]),
			"thumbs_square"			=> $settings["thumb_type"] == 1 ? 1 : 0,
		);
		return tpl()->parse($TEMPLATE_NAME, $replace);
	}

	/**
	* Get prev and next photos links
	*/
	function _get_prev_next_links ($user_info = array(), $cur_folder_id = 0) {
		if (empty($user_info)) {
			return false;
		}
		// Prepare links to the adjacent photos
		if (!module('gallery')->GET_PREV_NEXT_PHOTOS) {
			return false;
		}
		// Get max privacy
		$max_privacy		= module('gallery')->_get_max_privacy($user_info["id"]);
		$max_level			= 1;
		$only_same_folder	= module('gallery')->MINI_THUMBS_SAME_FOLDER;
		// Generate SQL for the access checks
		if (!module('gallery')->is_own_gallery) {
			$PHOTOS_ACCESS_SQL = " AND is_public='1' ";
/*
			$PHOTOS_ACCESS_SQL = 
				" AND folder_id IN( 
					SELECT id 
					FROM ".db('gallery_folders')." 
					WHERE privacy<=".intval($max_privacy)." 
						AND active='1' 
						AND password='' 
						AND user_id=".intval($user_info["id"])."
				)";
*/
		}
		// Limit links to the same folder
		if ($only_same_folder && $cur_folder_id) {
			$PHOTOS_ACCESS_SQL = "";
			// Check if user already entered password
			if (!module('gallery')->is_own_gallery) {
				$_entered_pswd	= $_SESSION[module('gallery')->SESSION_PSWD_FIELD][$cur_folder_id];
				$_folder_pswd	= $this->_cur_folder_info["password"];
				if ($_folder_pswd && $_entered_pswd == $_folder_pswd) {
					$PHOTOS_ACCESS_SQL = "";
				} else {
					$PHOTOS_ACCESS_SQL = " AND is_public='1' ";
				}
			}
			$PHOTOS_ACCESS_SQL .= " AND folder_id='".intval($cur_folder_id)."' ";
		}
		$cur_photo_type = "medium";
		// First - get all user's photos info (skip protected photos)
		$Q = db()->query(
			"SELECT * 
			FROM ".db('gallery_photos')." 
			WHERE user_id=".intval(module('gallery')->_photo_info["user_id"])." 
				AND active='1' 
				".$PHOTOS_ACCESS_SQL." 
			ORDER BY cat_id ASC,id ASC"
		);

// TODO: global photos sorting		general_sort_id ASC, 

		while ($A = db()->fetch_assoc($Q)) {
			$_fs_thumb_path = module('gallery')->_photo_fs_path($A, $cur_photo_type);
			// Skip non-existed files
			if (!file_exists($_fs_thumb_path) || !@filesize($_fs_thumb_path)) {
				continue;
			}
			$this->_all_photos[$A["id"]] = $A;
		}
		// Try to find next photo (only if photos > 1)
		if (empty($this->_all_photos) || count($this->_all_photos) <= 1) {
			return false;
		}
		$photos_ids = array_keys($this->_all_photos);
		$_last_pos	= count($photos_ids) - 1;
		$_cur_photo_pos = 0;
		$prev_photo_id	= 0;
		$next_photo_id	= 0;
		// First find array position for the requested photo
		foreach ((array)$photos_ids as $_cur_pos => $_photo_id) {
			if ($_photo_id == module('gallery')->_photo_info["id"]) {
				$_cur_photo_pos = $_cur_pos;
				break;
			}
		}
		// Try to get prev photo id
		// Cur photo is first
		if (empty($_cur_photo_pos)) {
			$prev_photo_id = $photos_ids[$_last_pos];
		// Cur photo is last
		} elseif ($_cur_photo_pos == $_last_pos) {
			$prev_photo_id = $photos_ids[$_cur_photo_pos - 1];
		} else {
			$prev_photo_id = $photos_ids[$_cur_photo_pos - 1];
		}
		// Try to get next photo id
		// Cur photo is first
		if (empty($_cur_photo_pos)) {
			$next_photo_id = $photos_ids[$_cur_photo_pos + 1];
		// Cur photo is last
		} elseif ($_cur_photo_pos == $_last_pos) {
			$next_photo_id = $photos_ids[0];
		} else {
			$next_photo_id = $photos_ids[$_cur_photo_pos + 1];
		}
		// Prepare links to the prev and next photos
		if (!empty($prev_photo_id)) {
			if (module('gallery')->HIDE_TOTAL_ID) {
				$prev_photo_id = $this->_all_photos[$prev_photo_id]["id2"];
			}
			$prev_photo_link = "./?object=".'gallery'."&action=".$_GET["action"]."&id=".intval($prev_photo_id);
		}
		if (!empty($next_photo_id)) {
			if (module('gallery')->HIDE_TOTAL_ID) {
				$next_photo_id = $this->_all_photos[$next_photo_id]["id2"];
			}
			$next_photo_link = "./?object=".'gallery'."&action=".$_GET["action"]."&id=".intval($next_photo_id);
		}
		return array($prev_photo_link, $next_photo_link);
	}

	/**
	* Display mini thumbs
	*/
	function _show_mini_thumbs ($user_info = array(), $params = array()) {
		if (empty($user_info)) {
			return false;
		}
		// Check if needed
		if (!module('gallery')->FOR_MEDIUM_NUM_MINI_THUMBS) {
			return false;
		}
		// Temporary chained with prev_next_links
		if (empty($this->_all_photos) || count($this->_all_photos) <= 1) {
			return false;
		}
		$cur_photo_type		= "ad thumbnail";
		$settings = module('gallery')->CUR_SETTINGS;
		// Prepare arrays
		$photos_ids		= array_keys($this->_all_photos);
		$_num_photos	= count($photos_ids);
		$_last_pos		= $_num_photos - 1; // First is "0"
		// First find array position for the requested photo
		$_cur_photo_pos = 0;
		foreach ((array)$photos_ids as $_cur_pos => $_photo_id) {
			if ($_photo_id == module('gallery')->_photo_info["id"]) {
				$_cur_photo_pos = $_cur_pos;
				break;
			}
		}
		$mini_positions = array();
		// Filter photos to display in mini thumbs
		$num_mini_thumbs = module('gallery')->FOR_MEDIUM_NUM_MINI_THUMBS;
		if (module('gallery')->MINI_THUMBS_SHOW_ALL) {
			$num_mini_thumbs = $_num_photos;
		}
		if ($_num_photos < module('gallery')->FOR_MEDIUM_NUM_MINI_THUMBS) {
			$num_mini_thumbs = $_num_photos;
		}
		$_num_mini_prev = floor($num_mini_thumbs / 2);
		$_num_mini_next = $num_mini_thumbs - $_num_mini_prev - 1;
		// Prepare prev
		$_prev_array = array();
		foreach (array_reverse((array)$photos_ids, true) as $_cur_pos => $_photo_id) {
			if ($_cur_photo_pos == $_cur_pos || $_cur_pos > $_cur_photo_pos) {
				continue;
			}
			if (count($_prev_array) >= $_num_mini_prev) {
				break;
			}
			$_prev_array[$_cur_pos] = $_cur_pos;
		}
		// Get some circular links for prev
		if (count($_prev_array) < $_num_mini_prev) {
			foreach (array_reverse((array)$photos_ids, true) as $_cur_pos => $_photo_id) {
				if ($_cur_photo_pos == $_cur_pos) {
					continue;
				}
				if (count($_prev_array) >= $_num_mini_prev) {
					break;
				}
				$_prev_array[$_cur_pos] = $_cur_pos;
			}
		}
		// Prepare next
		$_next_array = array();
		foreach ((array)$photos_ids as $_cur_pos => $_photo_id) {
			if ($_cur_photo_pos == $_cur_pos || $_cur_pos < $_cur_photo_pos) {
				continue;
			}
			if (count($_next_array) >= $_num_mini_next) {
				break;
			}
			$_next_array[$_cur_pos] = $_cur_pos;
		}
		// Get some circular links for next
		if (count($_next_array) < $_num_mini_next) {
			foreach ((array)$photos_ids as $_cur_pos => $_photo_id) {
				if ($_cur_photo_pos == $_cur_pos) {
					continue;
				}
				if (count($_next_array) >= $_num_mini_next) {
					break;
				}
				$_next_array[$_cur_pos] = $_cur_pos;
			}
		}
		// Result positions
		foreach ((array)array_reverse($_prev_array, true) as $_cur_pos) {
			$mini_positions[] = $_cur_pos;
		}
		// Current photo is required
		$mini_positions[] = $_cur_photo_pos;
		foreach ((array)$_next_array as $_cur_pos) {
			$mini_positions[] = $_cur_pos;
		}
		// Override order when show all
		if (module('gallery')->MINI_THUMBS_SHOW_ALL) {
			$mini_positions = array_keys($photos_ids);
		}
		// Process photos to display
		foreach ((array)$mini_positions as $_cur_pos) {
			$_photo_id = $photos_ids[$_cur_pos];

			$A = $this->_all_photos[$_photo_id];

			// Prepare other photo info
			$other_info = array();
			if (!empty($A["other_info"])) {
				$other_info = unserialize($A["other_info"]);
			}
			// Check if we need to update other info
			if (empty($other_info[$cur_photo_type]["w"]) || empty($other_info[$cur_photo_type]["h"])) {
				$other_info = module('gallery')->_update_other_info($A);
			}
			// Prepare real dimensions
			$real_w = $other_info[$cur_photo_type]["w"];
			$real_h = $other_info[$cur_photo_type]["h"];
			$_real_coef = $real_h ? $real_w / $real_h : 0;
			// Limits for the current photo size
			$_max_w = module('gallery')->PHOTO_TYPES[$cur_photo_type]["max_x"];
			$_max_h = module('gallery')->PHOTO_TYPES[$cur_photo_type]["max_y"];
			// Force cut photo dimensions
			$force_resize = false;
			if ($_max_w && $real_w > $_max_w) {
				$real_w = $_max_w * ($real_w > $real_h ? 1 : $_real_coef);
				$force_resize = true;
			}
			if ($_max_h && $real_h > $_max_h) {
				$real_h = $_max_h * ($real_w > $real_h ? $_real_coef : 1);
				$force_resize = true;
			}
			// Check CPU Load
			if (conf('HIGH_CPU_LOAD') == 1) {
				$force_resize	= false;
			}
			if ($force_resize) {
				$_fs_img_path = module('gallery')->_photo_fs_path($A, $cur_photo_type);
				common()->make_thumb($_fs_img_path, $_fs_img_path, $_max_w, $_max_h);
				$other_info = module('gallery')->_update_other_info($A);
			}
			$_web_photo_id = $A["id"];
			if (module('gallery')->HIDE_TOTAL_ID) {
				$_web_photo_id = $A["id2"];
			}
			// Prepare array for template
			$thumbs[$_photo_id] = array(
				"photo_id"		=> intval($_web_photo_id),
				"photo_name"	=> _prepare_html(!empty($A["name"]) ? $A["name"] : _display_name($user_info)." photo"),
				"photo_url"		=> process_url("./?object=gallery&action=show_medium_size&id=".$_web_photo_id),
				"img_src"		=> module('gallery')->_photo_web_path($A, $cur_photo_type),
				"name"			=> _display_name($user_info),
				"real_w"		=> intval($real_w),
				"real_h"		=> intval($real_h),
				"is_current"	=> $_cur_photo_pos == $_cur_pos ? 1 : 0,
			);
		}
		// Prepare template
		$replace = array(
			"thumbs"			=> $thumbs,
			"thumbs_square"		=> $settings["thumb_type"] == 1 ? 1 : 0,
			"prev_photo_link"	=> $params["prev_photo_link"],
			"next_photo_link"	=> $params["next_photo_link"],
		);
		return tpl()->parse('gallery'."/mini_thumbs", $replace);
	}

	/**
	* Display user photos
	*/
	function _show_user_photos ($user_info = array(), $FOLDER_ID = 0, $stpl_prefix = "show_") {
		$user_id = intval($user_info["id"]);
		// Get user gallery settings
		module('gallery')->CUR_SETTINGS = module('gallery')->_get_settings($user_id);
		$settings = module('gallery')->CUR_SETTINGS;
		// Get template name postfix
		$stpl_postfix = "";
		if ($settings["layout_type"] == 1 && $stpl_prefix == "show_") {
			$stpl_postfix = "_simple";
			module('gallery')->USER_GALLERY_HOME_SHOW = "latest";
			module('gallery')->STATS_NUM_LATEST = 100;
		}
		// Get available user folders
		if (empty(module('gallery')->_user_folders_infos)) {
			module('gallery')->_user_folders_infos = module('gallery')->_get_user_folders($user_info["id"]);
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = module('gallery')->_user_folders_infos[$FOLDER_ID];
		}
		// Get max privacy
		$max_privacy	= module('gallery')->_get_max_privacy($user_info["id"]);
		$max_level		= 1;
		// Set some global vars for other modules
		module('gallery')->_author_id	= intval($user_info["id"]);
		module('gallery')->_author_name = _display_name($user_info);
		if (!empty($FOLDER_ID)) {
			module('gallery')->_folder_name = _prepare_html($cur_folder_info["title"]);
		}
		// ###########################
		// Access checks
		// ###########################
		if (!module('gallery')->is_own_gallery) {
			// Remove denied folders from list
			foreach ((array)module('gallery')->_user_folders_infos as $_folder_id => $_folder_info) {
				if ($_folder_info["privacy"] && $_folder_info["privacy"] > $max_privacy) {
					unset(module('gallery')->_user_folders_infos[$_folder_id]);
				}
			}
			if (!empty($cur_folder_info)) {
				// Check privacy permissions
				if (!module('gallery')->_privacy_check($cur_folder_info["privacy"], module('gallery')->_photo_info["privacy"], $user_info["id"])) {
					return _e("You are not allowed to view this gallery folder");
				}
				// Check for password for protected gallery
				if (!empty($cur_folder_info["password"])) {
					$PASSWORD_MATCHED = $_SESSION[module('gallery')->SESSION_PSWD_FIELD][$FOLDER_ID] == $cur_folder_info["password"];
				}
				// Display form to enter the password
				if (!empty($cur_folder_info["password"]) && !$PASSWORD_MATCHED && MAIN_TYPE_USER) {
					return module('gallery')->_enter_pswd($FOLDER_ID);
				}
			}
		}
		$_show_featured = module('gallery')->USER_GALLERY_HOME_SHOW == "featured" ? true : false;
		$featured_sql = "";
		if (empty($FOLDER_ID) && $_show_featured) {
			$featured_sql = " AND is_featured = '1' ";
		}
		// Generate SQL for the access checks
		if (!module('gallery')->is_own_gallery) {
			$PHOTOS_ACCESS_SQL = 
				" AND folder_id IN( 
					SELECT id 
					FROM ".db('gallery_folders')." 
					WHERE privacy<=".intval($max_privacy)." 
						/*AND content_level<=".intval($max_level)."*/ 
						".($PASSWORD_MATCHED ? "AND (id=".intval($FOLDER_ID)." OR password='')" : "AND password=''")."
						AND active='1' 
						AND user_id=".intval($user_info["id"])."
				)";
		}
		$_sort_id_field = $_GET["action"] == "view_folder" ? "folder_sort_id" : "general_sort_id";
		// Get all user photos
		$Q = db()->query(
			"SELECT * FROM ".db('gallery_photos')." 
			WHERE user_id=".intval($user_id)." "
				.$PHOTOS_ACCESS_SQL
				.$featured_sql
				." ORDER BY ".$_sort_id_field." ASC/*, add_date DESC*/"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$photos_array[$A["id"]] = $A;
			$photos_by_folders[$A["folder_id"]][$A["id"]] = $A["id"];
		}
		// Prepare folders for template
		foreach ((array)module('gallery')->_user_folders_infos as $A) {
			$folders_array[$A["id"]] = array(
				"title"			=> _prepare_html($A["title"]),
				"link"			=> "./?object=".'gallery'."&action=view_folder&id=".(module('gallery')->HIDE_TOTAL_ID ? $A["id2"] : $A["id"]),
				"is_default"	=> intval((bool)$A["is_default"]),
			);
		}
		$this->_tags = module('gallery')->_show_tags(array_keys((array)$photos_array), array("simple" => 1));

		// Main user's page
		if (empty($FOLDER_ID)) {
			$photos = _class_safe('gallery_stats', 'modules/gallery/')->_show_latest_user_photos($user_info, $_show_featured, $photos_array);
		// Inside selected folder
		} else {
			// Process photos
			foreach ((array)$photos_array as $photo_id => $photo_info) {
				if ($photo_info["folder_id"] != $FOLDER_ID) {
					continue;
				}
				$photos .= module('gallery')->_show_photo_item($photos_array[$photo_id], $stpl_prefix);
			}
		}
		// Check if user has uploaded max number of photos
		if (!empty(module('gallery')->MAX_TOTAL_PHOTOS)) {
			$limit_reached = count($photos_array) >= module('gallery')->MAX_TOTAL_PHOTOS;
		}
		// Prepare show in ads
		$SHOW_IN_ADS_ALLOWED = 0;
		if ($cur_folder_info["content_level"] <= 1 && $cur_folder_info["privacy"] <= 1 && $cur_folder_info["password"] == "") {
			$SHOW_IN_ADS_ALLOWED = 1;
		}
		$_web_folder_id = $FOLDER_ID;
		if (module('gallery')->HIDE_TOTAL_ID && $FOLDER_ID) {
			$_web_folder_id = $cur_folder_info["id2"];
		}
		// Sorting templates (To use inside JavaScript code)
		$_sort_add		= $_GET["action"] == "view_folder" ? "_in_folder" : "";
		$_sort_link_tpl	= process_url("./?object=".$_GET["object"]."&action=sort_photo&id={id}&page=up".$_sort_add);
		// Preocess template
		$replace = array(
			"is_logged_in"			=> intval((bool) main()->USER_ID),
			"is_own_gallery"		=> module('gallery')->is_own_gallery,
			"user_name"				=> module('gallery')->_author_name,
			"user_avatar"			=> _show_avatar($user_info["id"], module('gallery')->_author_name, 1, 1),
			"user_profile_link"		=> _profile_link($user_info["id"]),
			"add_photo_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=add_photo".($_web_folder_id ? "&id=".$_web_folder_id : ""). _add_get(array("page")) : "",
			"page_link"				=> process_url("./?object=".'gallery'."&action=".$_GET["action"].($_GET["id"] ? "&id=".$_GET["id"] : ""). _add_get(array("page"))),
			"user_gallery_link"		=> $user_info["id"] ? "./?object=".'gallery'."&action=show_gallery".(module('gallery')->HIDE_TOTAL_ID ? "" : "&id=".$user_info["id"]). _add_get(array("page")) : "",
			"max_photos"			=> intval(module('gallery')->MAX_TOTAL_PHOTOS),
			"user_id"				=> intval(main()->USER_ID),
			"add_form"				=> $stpl_prefix == "edit_" && module('gallery')->is_own_gallery && !$limit_reached ? module('gallery')->add_photo() : "",
			"photos"				=> $photos,
			"folders"				=> $folders_array,
			"folder_name"			=> $FOLDER_ID ? _prepare_html(module('gallery')->_user_folders_infos[$FOLDER_ID]["title"]) : "",
			"folder_comment"		=> nl2br(_prepare_html($cur_folder_info["comment"])),
			"folder_add_date"		=> $cur_folder_info["add_date"] ? _format_date($cur_folder_info["add_date"]) : "",
			"folder_content_level"	=> module('gallery')->_content_levels[$cur_folder_info["content_level"]],
			"folder_privacy"		=> module('gallery')->_privacy_types[$cur_folder_info["privacy"]],
			"folder_allow_comments"	=> module('gallery')->_comments_types[$cur_folder_info["allow_comments"]],
			"add_folder_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=add_folder". _add_get(array("page")) : "",
			"edit_folder_link"		=> module('gallery')->is_own_gallery && $FOLDER_ID ? "./?object=".'gallery'."&action=edit_folder&id=".$_web_folder_id. _add_get(array("page")) : "",
			"delete_folder_link"	=> module('gallery')->is_own_gallery && $FOLDER_ID ? "./?object=".'gallery'."&action=delete_folder&id=".$_web_folder_id. _add_get(array("page")) : "",
			"show_ads_denied"		=> module('gallery')->is_own_gallery ? intval(!$SHOW_IN_ADS_ALLOWED) : "",
			"users_comments_link"	=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=search_comments". _add_get(array("page")) : "",
			"settings_link"			=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=settings" : "",
			"show_featured"			=> intval((bool)$_show_featured),
			"thumbs_square"			=> $settings["thumb_type"] == 1 ? 1 : 0,
			"slideshow_mode"		=> intval($settings["slideshow_mode"]),
			"sort_link_tpl"			=> module('gallery')->is_own_gallery ? $_sort_link_tpl : "",
		);
		return tpl()->parse('gallery'."/".$stpl_prefix."main".$stpl_postfix, $replace);
	}

	/**
	* Show photo item
	*/
	function _show_photo_item ($photo_info = array(), $stpl_prefix = "show_") {
		$PARAMS = array();
		// Second argument could be params array
		if (is_array($stpl_prefix)) {
			$PARAMS = $stpl_prefix;
			$stpl_prefix = "";
		}
		if (!empty($PARAMS["stpl_prefix"])) {
			$stpl_prefix = $PARAMS["stpl_prefix"];
		}
		if (!$stpl_prefix) {
			$stpl_prefix = "show_";
		}
		$ITEM_STPL = $PARAMS["stpl_full_path"];
		if (empty($ITEM_STPL)) {
			$ITEM_STPL = 'gallery'."/".$stpl_prefix."photo_item";
		}
		// Prepare user name
		$user_name = $PARAMS["user_name"];
		if (!strlen($user_name)) {
			if (isset(module('gallery')->_users_names[$photo_info["user_id"]])) {
				$user_name = module('gallery')->_users_names[$photo_info["user_id"]];
			} else {
				$user_name = _display_name($GLOBALS['user_info']);
			}
		}
		$cur_photo_type = module('gallery')->PHOTO_ITEM_DISPLAY_TYPE;
		$_fs_img_path = module('gallery')->_photo_fs_path($photo_info, $cur_photo_type);
		// Skip empty images
		if (empty($_fs_img_path) || !file_exists($_fs_img_path) || !filesize($_fs_img_path)) {
			if (module('gallery')->SKIP_NOT_FOUND_PHOTOS) {
				return false;
			}
		}
		// Get available user folders
		$user_folders_infos = module('gallery')->_get_user_folders($photo_info["user_id"]);
		// Get photo folder info
		$FOLDER_ID = $photo_info["folder_id"];
		if (empty($FOLDER_ID)) {
			$FOLDER_ID = module('gallery')->_fix_and_get_folder_id($photo_info);
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders_infos[$FOLDER_ID];
		}
		if (!isset(module('gallery')->CUR_SETTINGS)) {
			module('gallery')->CUR_SETTINGS = module('gallery')->_get_settings($photo_info["user_id"]);
		}
		$settings = module('gallery')->CUR_SETTINGS;
		// Prepare show in ads
		$SHOW_IN_ADS_ALLOWED = 0;
		if ($cur_folder_info["content_level"] <= 1 && $cur_folder_info["privacy"] <= 1 && $cur_folder_info["password"] == "") {
			$SHOW_IN_ADS_ALLOWED = 1;
		}
		// Prepare other photo info
		$other_info = array();
		if (!empty($photo_info["other_info"])) {
			$other_info = unserialize($photo_info["other_info"]);
		}
		// Prepare real dimensions
		$real_w = $other_info[$cur_photo_type]["w"];
		$real_h = $other_info[$cur_photo_type]["h"];
		$_real_coef = $real_h ? $real_w / $real_h : 0;
		// Limits for the current photo size
		$_max_w = module('gallery')->PHOTO_TYPES[$cur_photo_type]["max_x"];
		$_max_h = module('gallery')->PHOTO_TYPES[$cur_photo_type]["max_y"];
		// Force cut photo dimensions
		$force_resize = false;
		if ($_max_w && $real_w > $_max_w) {
			$real_w = $_max_w * ($real_w > $real_h ? 1 : $_real_coef);
			$force_resize = true;
		}
		if ($_max_h && $real_h > $_max_h) {
			$real_h = $_max_h * ($real_w > $real_h ? $_real_coef : 1);
			$force_resize = true;
		}
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			$force_resize	= false;
		}
		if ($force_resize && $cur_photo_type != "original") {
			common()->make_thumb($_fs_img_path, $_fs_img_path, $_max_w, $_max_h);
			$other_info = module('gallery')->_update_other_info($photo_info);
		}

		$tags_block = module('gallery')->_show_tags($photo_info["id"]);
		$tags_block = $tags_block[$photo_info["id"]];

		$_web_photo_id = $photo_info["id"];
		if (module('gallery')->HIDE_TOTAL_ID) {
			$_web_photo_id = $photo_info["id2"];
		}
		// Special for tags
		$show_pswd_protected = false;
		if ($stpl_prefix == "tag_search_" && strlen($cur_folder_info["password"])) {
			$show_pswd_protected = true;
		}

		// Sort photo links here
// TODO: add more intellect here
		$_sort_link		= "./?object=".$_GET["object"]."&action=sort_photo&id=".$_web_photo_id."&page=";
		$_sort_add		= $_GET["action"] == "view_folder" ? "_in_folder" : "";
		$_sort_up_link	= $_sort_link."up".$_sort_add;
		$_sort_down_link= $_sort_link."down".$_sort_add;

		// Process template
		$replace = array(
			"photo_number"			=> intval(++$GLOBALS["_photo_items_counter"]),
			"photo_id"				=> intval($photo_info["id"]),
			"photo_id2"				=> module('gallery')->HIDE_TOTAL_ID ? intval($photo_info["id2"]) : $photo_info["id"],
			"img_src"				=> !$show_pswd_protected ? module('gallery')->_photo_web_path($photo_info, $cur_photo_type) : "",
			"photo_name"			=> _prepare_html($photo_info["name"]),
			"photo_desc"			=> _prepare_html($photo_info["desc"]),
			"medium_size_link"		=> "./?object=".'gallery'."&action=show_medium_size&id=".$_web_photo_id. _add_get(array("page")),
			"large_size_link"		=> "./?object=".'gallery'."&action=show_full_size&id=".$_web_photo_id. _add_get(array("page")),
			"edit_photo_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=edit_photo&id=".$_web_photo_id. _add_get(array("page")) : "",
			"delete_photo_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=delete_photo&id=".$_web_photo_id. _add_get(array("page")) : "",
			"change_show_ads_link"	=> module('gallery')->is_own_gallery && !$params["no_show_in_ads"] ? "./?object=".'gallery'."&action=change_show_ads&id=".$_web_photo_id. _add_get(array("page")) : "",
			"make_default_link"		=> module('gallery')->is_own_gallery && !$params["no_make_default"] ? "./?object=".'gallery'."&action=make_default&id=".$_web_photo_id. _add_get(array("page")) : "",
			"show_in_ads"			=> $SHOW_IN_ADS_ALLOWED && module('gallery')->is_own_gallery && !$params["no_show_in_ads"] ? intval((bool) $photo_info["show_in_ads"]) : -1,
			"is_own_gallery"		=> intval((bool) module('gallery')->is_own_gallery),
			"need_divider"			=> !($GLOBALS["_photo_items_counter"] % module('gallery')->PHOTOS_IN_COLUMN),
			"user_name"				=> _prepare_html($user_name),
			"user_id"				=> intval($photo_info["user_id"]),
			"rate_box"				=> $rate_box,
			"real_w"				=> intval($real_w),
			"real_h"				=> intval($real_h),
			"folder_name"			=> $FOLDER_ID ? _prepare_html($user_folders_infos[$FOLDER_ID]["title"]) : "",
			"folder_comment"		=> nl2br(_prepare_html($cur_folder_info["comment"])),
			"folder_add_date"		=> $cur_folder_info["add_date"] ? _format_date($cur_folder_info["add_date"]) : "",
			"folder_content_level"	=> module('gallery')->_content_levels[$cur_folder_info["content_level"]],
			"folder_privacy"		=> module('gallery')->_privacy_types[$cur_folder_info["privacy"]],
			"show_ads_denied"		=> module('gallery')->is_own_gallery ? intval(!$SHOW_IN_ADS_ALLOWED) : "",
			"rate_enabled"			=> intval((bool) module('gallery')->ALLOW_RATE),
			"rate_allowed"			=> module('gallery')->ALLOW_RATE ? intval((bool) $photo_info["allow_rate"]) : 0,
			"rating"				=> $photo_info["allow_rate"] ? round($photo_info["rating"], 1) : "",
			"rate_num_votes"		=> $photo_info["allow_rate"] ? intval($photo_info["num_votes"]) : "",
			"rate_last_voted"		=> $photo_info["allow_rate"] ? _format_date($photo_info["last_vote_date"]) : "",
			"rate_block"			=> $photo_info["allow_rate"] ? module('gallery')->_show_rate_block($photo_info) : "",
			"change_rate_link"		=> module('gallery')->ALLOW_RATE && module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=change_rate_allowed&id=".$_web_photo_id. _add_get(array("page")) : "",
			"tagging_enabled"		=> intval((bool) module('gallery')->ALLOW_TAGGING),
			"tagging_allowed"		=> module('gallery')->ALLOW_TAGGING ? intval((bool) $photo_info["allow_tagging"]) : 0,
			"change_tagging_link"	=> module('gallery')->ALLOW_TAGGING && module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=change_tagging_allowed&id=".$_web_photo_id. _add_get(array("page")) : "",
			"tags"					=> module('gallery')->ALLOW_TAGGING && !empty($tags) ? $tags : "",
			"allow_add_tag"			=> $allow_add_tag ? 1 : 0,
			"edit_tag_link"			=> $allow_add_tag ? process_url("./?object=".'gallery'."&action=edit_tags_popup&id=".$_web_photo_id. _add_get(array("page"))) : "",
			"tags_block"			=> $tags_block,
			"show_pswd_protected"	=> $show_pswd_protected ? 1 : 0,
			"slideshow_mode"		=> intval($settings["slideshow_mode"]),
			"img_m_src"				=> !$show_pswd_protected ? module('gallery')->_photo_web_path($photo_info, "medium") : "",
			"sort_up_link"			=> module('gallery')->is_own_gallery ? $_sort_up_link : "",
			"sort_down_link"		=> module('gallery')->is_own_gallery ? $_sort_down_link : "",
		);
		return tpl()->parse($ITEM_STPL, $replace);
	}
}
