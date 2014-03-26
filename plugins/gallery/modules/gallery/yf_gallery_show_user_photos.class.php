<?php

/**
*/
class yf_gallery_show_user_photos {

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
		module('gallery')->_tags = module('gallery')->_show_tags(array_keys((array)$photos_array), array("simple" => 1));

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
			"users_comments_link"	=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=search_comments". _add_get(array("page")) : "",
			"settings_link"			=> module('gallery')->is_own_gallery ? "./?object=".'gallery'."&action=settings" : "",
			"show_featured"			=> intval((bool)$_show_featured),
			"thumbs_square"			=> $settings["thumb_type"] == 1 ? 1 : 0,
			"slideshow_mode"		=> intval($settings["slideshow_mode"]),
			"sort_link_tpl"			=> module('gallery')->is_own_gallery ? $_sort_link_tpl : "",
		);
		return tpl()->parse('gallery'."/".$stpl_prefix."main".$stpl_postfix, $replace);
	}
}
