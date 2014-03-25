<?php

/**
* Gallery settings editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_settings {

	/**
	* Edit gallery settings
	*/
	function settings () {
		// Check if user is member
		if (empty(main()->_user_info)) {
			return _error_need_login();
		}
		// Ban check
		if (main()->_user_info["ban_images"]) {
			return module('gallery')->_error_msg("ban_images");
		}
		// Try to get user settings
		$GALLERY_SETTINGS = module('gallery')->_get_settings(main()->USER_ID);
		// Check posted data and save
		if (!empty($_POST["go"])) {
			// Check required data
			if (!isset(module('gallery')->_thumb_types[$_POST["thumb_type"]])) {
				_re("Wrong thumb type");
			}
			if (!isset(module('gallery')->MEDIUM_SIZES[$_POST["medium_size"]])) {
				_re("Wrong medium photo size");
			}
			if (!isset(module('gallery')->_layout_types[$_POST["layout_type"]])) {
				_re("Wrong layout type");
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Generate SQL
				$sql = array(
					"title"			=> _es($_POST["title"]),
					"desc"			=> _es($_POST["desc"]),
					"privacy"		=> _es($_POST["privacy"]),
					"allow_comments"=> _es($_POST["allow_comments"]),
					"thumb_type"	=> _es($_POST["thumb_type"]),
					"medium_size"	=> intval($_POST["medium_size"]),
					"layout_type"	=> _es($_POST["layout_type"]),
					"thumbs_loc"	=> intval($_POST["thumbs_loc"]),
					"thumbs_in_row"	=> intval($_POST["thumbs_in_row"]),
					"slideshow_mode"=> intval($_POST["slideshow_mode"]),
				);
				if (isset($_POST["allow_tagging"])) {
					$sql["allow_tagging"]	= _es($_POST["allow_tagging"]);
				}
				if (isset($_POST["allow_rate"])) {
					$sql["allow_rate"]		= _es($_POST["allow_rate"]);
				}
				db()->UPDATE("gallery_settings", $sql, "user_id=".intval(main()->USER_ID));
				// Update cache
				$GLOBALS['_gal_settings'][main()->USER_ID]["thumb_type"]	= $_POST["thumb_type"];
				$GLOBALS['_gal_settings'][main()->USER_ID]["medium_size"]	= $_POST["medium_size"];
				// Regenerate thumbs (if changed)
				if ($_POST["thumb_type"] != $GALLERY_SETTINGS["thumb_type"]) {
					$this->_regenerate_format("thumbnail");
					$this->_regenerate_format("ad thumbnail");
				}
				// Regenerate medium sizes (if changed)
				if ($GALLERY_SETTINGS["medium_size"] && $_POST["medium_size"] != $GALLERY_SETTINGS["medium_size"]) {
					$this->_regenerate_format("medium");
				}
// TODO: connect these privacy, allow_comments, allow_tagging and allow_rate everywhere in other gallery edit forms
				// Return user back
				return js_redirect("./?object=".'gallery'."&action=".$_GET["action"]._add_get(array("page")));
			}
		}
		// Merge data
		foreach ((array)$GALLERY_SETTINGS as $k => $v) {
			$DATA[$k] = $v;
		}
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		// Show form
		if (empty($_POST["go"])) {
			$replace = array(
				"form_action"		=> "./?object=".'gallery'."&action=".$_GET["action"]._add_get(array("page")),
				"error_message"		=> _e(),
				"privacy_box"		=> module('gallery')->_box("privacy", $DATA["privacy"]),
				"comments_box"		=> module('gallery')->_box("allow_comments", $DATA["allow_comments"]),
				"tagging_box"		=> module('gallery')->ALLOW_TAGGING ? module_safe('tags')->_mod_spec_settings(array("module" => "gallery", "object_id" => main()->USER_ID)) : "",
				"allow_rate_box"	=> module('gallery')->ALLOW_RATE ? module_safe('rating')->_mod_spec_settings(array("module" => "gallery", "object_id" => main()->USER_ID)) : "",
				"thumb_type_box"	=> module('gallery')->_box("thumb_type", $DATA["thumb_type"]),
				"medium_size_box"	=> module('gallery')->_box("medium_size", $DATA["medium_size"]),
				"layout_type_box"	=> module('gallery')->_box("layout_type", $DATA["layout_type"]),
				"thumbs_loc_box"	=> module('gallery')->_box("thumbs_loc", $DATA["thumbs_loc"]),
				"thumbs_in_row_box"	=> module('gallery')->_box("thumbs_in_row", $DATA["thumbs_in_row"]),
				"slideshow_mode_box"=> module('gallery')->_box("slideshow_mode", $DATA["slideshow_mode"]),
			);
			$body = tpl()->parse('gallery'."/edit_settings", $replace);
		}
		return $body;
	}

	/**
	* General method for retrieving users gallery settings
	*/
	function _get_settings($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		// Multiple ids at one time
		if (is_array($user_id)) {
			$get_user_ids	= $user_id;
			$users_settings = array();
			// Use cache
			foreach ((array)$get_user_ids as $k => $_user_id) {
				if (isset($GLOBALS['_gal_settings'][$_user_id])) {
					$users_settings[$_user_id] = $GLOBALS['_gal_settings'][$_user_id];
					unset($get_user_ids[$k]);
				}
			}
			if (!empty($get_user_ids)) {
				// Get from db first
				$Q = db()->query("SELECT * FROM ".db('gallery_settings')." WHERE user_id IN(".implode(",", $get_user_ids).")");
				while ($A = db()->fetch_assoc($Q)) {
					$_user_id = $A["user_id"];
					$users_settings[$_user_id] = $A;
					// Cache store
					$GLOBALS['_gal_settings'][$_user_id] = $users_settings[$_user_id];
				}
			}
			// Fix non-existed settings
			foreach ((array)$user_id as $_user_id) {
				if (!isset($users_settings[$_user_id])) {
					$users_settings[$_user_id] = $this->_start($_user_id);
					// Cache store
					$GLOBALS['_gal_settings'][$_user_id] = $users_settings[$_user_id];
				}
			}
			return $users_settings;
		// Single user_id
		} else {
			if (!empty(main()->USER_ID) && $user_id == main()->USER_ID && !empty($this->CUR_USER_SETTINGS)) {
				return module('gallery')->CUR_USER_SETTINGS;
			}
			// Use cache
			if (isset($GLOBALS['_gal_settings'][$user_id])) {
				return $GLOBALS['_gal_settings'][$user_id];
			}
			// Try to get settings from db
			$GALLERY_SETTINGS = db()->query_fetch("SELECT * FROM ".db('gallery_settings')." WHERE user_id=".intval($user_id));
			// Do create user settings (if not done yet)
			if (empty($GALLERY_SETTINGS)) {
				$GALLERY_SETTINGS = $this->_start($user_id);
			}
			// Cache store
			$GLOBALS['_gal_settings'][$user_id] = $GALLERY_SETTINGS;
			// Return result
			return $GALLERY_SETTINGS;
		}
	}

	/**
	* Start gallery settings
	*/
	function _start ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		$ACCOUNT_EXISTS = db()->query_num_rows("SELECT user_id FROM ".db('gallery_settings')." WHERE user_id=".intval($user_id));
		if ($ACCOUNT_EXISTS) {
			return false;
		}
		// Get default gallery settings
		$sql_array = module('gallery')->DEFAULT_SETTINGS;
		$sql_array["user_id"]	= intval($user_id);
		// Set global tags settings as defaults
		if (module('gallery')->ALLOW_TAGGING) {
			$default_tags_settings = module_safe('tags')->_mod_spec_settings(array("module" => "gallery", "object_id" => main()->USER_ID), module_safe('tags')->ALLOWED_GROUP);
		}
		$sql_array["allow_tagging"]	= intval($default_tags_settings);

		db()->INSERT("gallery_settings", $sql_array);

		return $sql_array;
	}

	/**
	* Regenerate given photo formats
	*/
	function _regenerate_format($format_name = "") {
		if (empty($format_name) || !isset(module('gallery')->PHOTO_TYPES[$format_name])) {
			return false;
		}
		$OBJ = _class("gallery_manage", 'modules/gallery/');
		// Process all photos
		$Q = db()->query("SELECT * FROM ".db('gallery_photos')." WHERE user_id=".intval(main()->USER_ID));
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_restore_all_sizes($A, $format_name);
		}
	}
}
