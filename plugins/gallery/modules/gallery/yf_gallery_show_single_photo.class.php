<?php

/**
*/
class yf_gallery_show_single_photo {

	/**
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
			"make_default_link"		=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=make_default&id=".$_web_photo_id. _add_get(array("page")) : "",
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
		$cur_photo_type		= "mini_thumbnail";
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
}
