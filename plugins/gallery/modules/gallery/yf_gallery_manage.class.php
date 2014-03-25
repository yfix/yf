<?php

/**
* Manage gallery photos (Add / Edit / Delete)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_manage {
	
	/**
	* Delete Photo
	*/
	function _refresh_images_in_browser($PHOTO_ID = 0) {
		$_images = array();
		// Gallery photo
		if ($_SESSION["_refresh_image_in_browser"]) {
			// Prevent double execution
			unset($_SESSION["_refresh_image_in_browser"]);
			$photo_info = array(
				"photo_id"	=> $PHOTO_ID,
				"user_id"	=> main()->USER_ID,
			);
			foreach ((array)module('gallery')->PHOTO_TYPES as $format_name => $format_info) {
				if ($format_name == "original") {
					continue;
				}
				$_images[] = module('gallery')->_photo_web_path($photo_info, $format_name);
			}
		}
		// Avatar
		if ($_SESSION["_refresh_avatar_in_browser"]) {
			// Prevent double execution
			unset($_SESSION["_refresh_avatar_in_browser"]);

			$_images[] = SITE_AVATARS_DIR. _gen_dir_path(main()->USER_ID). intval(main()->USER_ID). ".jpg";
		}
		$body .= "";
		if (!empty($_images)) {
			foreach ((array)$_images as $_src) {
				$body .= "<img src='".$_src ."?".module('gallery')->_cur_rand."' width='1' height='1' style='visibility:hidden;'>";
			}
			$body = "<span style='width:0px;height:0px;'>".$body."</span>";
		}
		return $body;
	}
	
	/**
	* Delete Photo
	*/
	function delete_photo($FORCE_PHOTO_ID = 0) {
		$photo_info = $this->_acl_manage_checks($FORCE_PHOTO_ID);
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$cur_folder_info = $this->_get_photo_folder_info($photo_info);
		// Process all types of photos
		foreach ((array)module('gallery')->PHOTO_TYPES as $format_name => $format_info) {
			$thumb_path = module('gallery')->_photo_fs_path($photo_info, $format_name);
			if (!file_exists($thumb_path)) {
				continue;
			}
			@unlink($thumb_path);
		}
		db()->query("DELETE FROM ".db('gallery_photos')." WHERE id=".intval($photo_info["id"])." LIMIT 1");
		module('gallery')->_sync_public_photos();
		_class_safe("user_stats")->_update(array("user_id" => main()->USER_ID));
		return js_redirect("./?object=".'gallery'."&action=".(!empty($photo_info["folder_id"]) ? "view_folder&id=".(module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]) : "show_gallery")._add_get(array("page")));
	}

	/**
	* Folder info
	*/
	function _get_photo_folder_info ($photo_info = array(), $FOLDER_ID = 0) {
		$user_folders = module('gallery')->_get_user_folders(main()->USER_ID);
		$def_folder_id = module('gallery')->_get_def_folder_id($user_folders);
		if (empty($FOLDER_ID) && !empty($def_folder_id)) {
			$FOLDER_ID = $def_folder_id;
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders[$FOLDER_ID];
		}
		return $cur_folder_info;
	}
	
	/**
	* Image cropper
	*/
	function crop_photo() {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		if (!module('gallery')->ALLOW_IMAGE_MANIPULATIONS) {
			return _e("Image manipulations not allowed.");
		}
		// Prepare other photo info
		$other_info = array();
		if (!empty($photo_info["other_info"])) {
			$other_info = unserialize($photo_info["other_info"]);
		}
		$cur_photo_type = "medium";
		// Prepare real dimensions
		$real_w = $other_info[$cur_photo_type]["w"];
		$real_h = $other_info[$cur_photo_type]["h"];
		// Check posted data and save
		if (!empty($_POST["go"])) {
			if (empty($_POST["params"])) {
				_re("Missing required params for image cropper.");
			}
			// Check for errors
			if (!common()->_error_exists()) {
				list($pos_left, $pos_top, $crop_width, $crop_height) = explode(";", $_POST["params"]);
			}
			if (!common()->_error_exists()) {
				// Check if crop width and height matches source ones (so we do not need to crop anything)
				if (!empty($crop_width) 
					&& !empty($crop_height) 
					&& ($crop_width < $real_w || $crop_height < $real_h)
				) {
					$this->_crop($photo_info, $pos_left, $pos_top, $crop_width, $crop_height);
				}
			}
			$_SESSION["_refresh_image_in_browser"] = true;
			return js_redirect("./?object=".'gallery'."&action=edit_photo&id=".(module('gallery')->HIDE_TOTAL_ID ? $photo_info["id2"] : $photo_info["id"]). _add_get(array("page")));
		}
		// Show form
		$_fs_thumb_src = module('gallery')->_photo_fs_path($photo_info, $cur_photo_type);
		$thumb_web_path = "";
		if (file_exists($_fs_thumb_src)) {
			$thumb_web_path = module('gallery')->_photo_web_path($photo_info, $cur_photo_type);
		}
		$replace = array(
			"form_action"		=> "./?object=".'gallery'."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
			"error_message"		=> _e(),
			"photo_name"		=> _prepare_html($_POST["photo_name"]),
			"photo_desc"		=> _prepare_html($_POST["photo_desc"]),
			"thumb_src"			=> $thumb_web_path,
			"user_id"			=> intval(main()->USER_ID),
			"back_link"			=> "./?object=".'gallery'."&action=edit_photo&id=".$photo_info["id"]._add_get(array("page")),
			"real_w"			=> intval($real_w),
			"real_h"			=> intval($real_h),
		);
		return tpl()->parse('gallery'."/crop_photo_form", $replace);
	}
	
	/**
	* Image rotater
	*/
	function rotate_photo() {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		if (!module('gallery')->ALLOW_IMAGE_MANIPULATIONS) {
			return _e("Image manipulations not allowed.");
		}
		// Check for errors
		if (!common()->_error_exists()) {
			if ($_GET["page"] == "cw")	$angle = -90;
			if ($_GET["page"] == "ccw") $angle = 90;
			if ($_GET["page"] == "180") $angle = 180;
			// Check rotate angle and go
			if (!empty($angle)) {
				$this->_rotate($photo_info, $angle);
			}

			$_SESSION["_refresh_image_in_browser"] = true;

			return js_redirect("./?object=".'gallery'."&action=edit_photo&id=".(module('gallery')->HIDE_TOTAL_ID ? $photo_info["id2"] : $photo_info["id"]). _add_get(array("page")));
		}
	}

	/**
	* Change show in ads status
	*/
	function change_show_ads () {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$cur_folder_info = $this->_get_photo_folder_info($photo_info);
		// Check number of photos to show in ads
		$num_photos_for_ads = db()->query_num_rows("SELECT id FROM ".db('gallery_photos')." WHERE user_id=".intval(main()->USER_ID)." AND show_in_ads='1'");
		if ($num_photos_for_ads >= module('gallery')->MAX_PHOTOS_FOR_ADS && $photo_info["show_in_ads"] == 0) {
			_re(t("You can use max @num photos in your ads!", array("@num" => intval(module('gallery')->MAX_PHOTOS_FOR_ADS))));
			return redirect("./?object=".'gallery'."&action=show_gallery"._add_get(array("page")), 1, _e());
		}
		// Do update db record
		db()->query(
			"UPDATE ".db('gallery_photos')." 
			SET show_in_ads='".($photo_info["show_in_ads"] ? 0 : 1)."' 
			WHERE id=".intval($photo_info["id"])
		);
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $photo_info["show_in_ads"] ? 0 : 1;
		} else {
			return js_redirect("./?object=".'gallery'."&action=view_folder&id=".(module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]));
		}
	}

	/**
	* Change rate if allowed
	*/
	function change_rate_allowed () {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$cur_folder_info = $this->_get_photo_folder_info($photo_info);
		// Do update db record
		db()->query(
			"UPDATE ".db('gallery_photos')." 
			SET allow_rate='".($photo_info["allow_rate"] ? 0 : 1)."' 
			WHERE id=".intval($photo_info["id"])
		);
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $photo_info["allow_rate"] ? 0 : 1;
		} else {
			return js_redirect("./?object=".'gallery'."&action=view_folder&id=".(module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]));
		}
	}

	/**
	* Change allow_tagging
	*/
	function change_tagging_allowed () {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$cur_folder_info = $this->_get_photo_folder_info($photo_info);
		// Do update db record
		db()->query(
			"UPDATE ".db('gallery_photos')." 
			SET allow_tagging='".($photo_info["allow_tagging"] ? 0 : 1)."' 
			WHERE id=".intval($photo_info["id"])
		);
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $photo_info["allow_tagging"] ? 0 : 1;
		} else {
			return js_redirect("./?object=".'gallery'."&action=view_folder&id=".(module('gallery')->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]));
		}
	}

	/**
	* Make given photo default
	*/
	function make_default () {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		// Create paths
		$thumb_path_1	= module('gallery')->_photo_fs_path($photo_info, "thumbnail");
		$thumb_path_2	= module('gallery')->_photo_fs_path($photo_info, "medium");
		$avatar_path_1	= INCLUDE_PATH. SITE_AVATARS_DIR. intval(main()->USER_ID). module('gallery')->IMAGE_EXT;
		$avatar_path_2	= INCLUDE_PATH. SITE_AVATARS_DIR. intval(main()->USER_ID). "_m". module('gallery')->IMAGE_EXT;
		// Copy thumb to the avatars folder
		// Hm... strange, but this case is most stable and work in most cases (instead of "rename" or "copy")
		file_put_contents($avatar_path_1, file_get_contents($thumb_path_1));
		file_put_contents($avatar_path_2, file_get_contents($thumb_path_2));

		$_SESSION["_refresh_avatar_in_browser"] = true;

		// Return user back
		return js_redirect("./?object=".'gallery'."&action=edit");
	}

	/**
	* Load photo
	*/
	function _load_photo ($_PHOTO = array(), $photo_info = array(), $is_local = false) {
		if (empty($_PHOTO) || empty($photo_info)) {
			return false;
		}
		$photo_path		= module('gallery')->_photo_fs_path($photo_info, "original");
		$photo_dir		= dirname($photo_path)."/";
		$new_file_name	= basename($photo_path);
		// Do upload image
		$upload_result = common()->upload_image($photo_path, $_PHOTO, module('gallery')->MAX_IMAGE_SIZE, $is_local);
		if (!$upload_result) {
			if (!common()->_error_exists()) {
				_e("Unrecognized error occured while uploading image");
			}
			return false;
		}
		// Fix original image size (if needed)
		$orig_max_x = module('gallery')->PHOTO_TYPES["original"]["max_x"];
		$orig_max_y = module('gallery')->PHOTO_TYPES["original"]["max_y"];
		if (!empty($orig_max_x) || !empty($orig_max_y)) {
			$orig_result = common()->make_thumb($photo_path, $photo_path, $orig_max_x, $orig_max_y);
			if (!$orig_result || !file_exists($photo_path) || !filesize($photo_path)) {
				_re("Cant resize original image");
				trigger_error("Cant resize original image \"".$photo_path."\"", E_USER_WARNING);
				// Cleanup uploaded file
				if (file_exists($photo_path)) {
					unlink($photo_path);
				}
				return false;
			}
		}
		// Resize all image sizes
		$this->_restore_all_sizes($photo_info);

		return $new_file_name;
	}

	/**
	* Do delete tried to upload photos if something wrong
	*/
	function _load_photo_rollback($photo_info = array()) {
		if (empty($photo_info)) {
			return false;
		}
		// Process all types of photos
		foreach ((array)module('gallery')->PHOTO_TYPES as $format_name => $format_info) {
			$thumb_path = module('gallery')->_photo_fs_path($photo_info, $format_name);
			if (!file_exists($thumb_path)) {
				continue;
			}
			@unlink($thumb_path);
		}
	}

	/**
	* Restore all image sizes based on original photo
	*/
	function _restore_all_sizes ($photo_info = array(), $ONLY_ONE_FORMAT = "") {
		if (empty($photo_info)) {
			return false;
		}
		$GALLERY_SETTINGS = module('gallery')->_get_settings($photo_info["user_id"]);

		$photo_path		= module('gallery')->_photo_fs_path($photo_info, "original");
		if (!file_exists($photo_path)) {
// TODO: maybe wee need to delete such photo?
			return false;
		}
		// Resize all image sizes
		foreach ((array)module('gallery')->PHOTO_TYPES as $format_name => $format_info) {
			if ($format_name == "original") {
				continue;
			}
			if (!empty($ONLY_ONE_FORMAT) && $ONLY_ONE_FORMAT != $format_name) {
				continue;
			}
			$new_thumb_path = module('gallery')->_photo_fs_path($photo_info, $format_name);
			$new_thumb_dir	= dirname($new_thumb_path)."/";
			// Create folder if not exists
			if (!file_exists($new_thumb_dir)) {
				_mkdir_m($new_thumb_dir, module('gallery')->DEF_DIR_MODE, 1);
			}
			// Try to create thumb
			$limit_x = $format_info["max_x"];
			$limit_y = $format_info["max_y"];
			// Override medium size
			if ($format_name == "medium" && $GALLERY_SETTINGS["medium_size"]) {
				$limit_x = $GALLERY_SETTINGS["medium_size"];
			}
			// Force square crop
			if ($GALLERY_SETTINGS["thumb_type"] == 1 && in_array($format_name, array("thumbnail", "mini_thumbnail"))) {
				@copy($photo_path, $new_thumb_path);

				$OBJ = _class("image_manip");
				$thumb_result = $OBJ->crop_box($new_thumb_path, $new_thumb_path, $limit_x, $limit_y);

			} else {

				// Do make thumb
				$thumb_result = common()->make_thumb($photo_path, $new_thumb_path, $limit_x, $limit_y);
			}
			// Check if file resized successfully
			if (!$thumb_result || !file_exists($new_thumb_path) || !filesize($new_thumb_path)) {
				_re("Cant resize image into format: ".$format_name);
				trigger_error("Cant resize image with new name = \"".$new_thumb_path."\"", E_USER_WARNING);
				return false;
			}
		}
		// Sync db record with new sizes
		if (!empty($photo_info)) {
			module('gallery')->_update_other_info($photo_info);
		}
	}

	/**
	* Do crop photo
	*/
	function _crop ($photo_info, $pos_left, $pos_top, $crop_width, $crop_height) {
		$original_path 	= module('gallery')->_photo_fs_path($photo_info, "original");
		$medium_path 	= module('gallery')->_photo_fs_path($photo_info, "medium");
		// get original and medium images width and height
		list($orig_width, $orig_height)		= getimagesize($original_path);
		list($medium_width, $medium_height)	= getimagesize($medium_path);
		// calculate scale ratio between fullsize and middlesize image
		$scale_w = $orig_width / $medium_width;
		$scale_h = $orig_height / $medium_height;
		$scale = ($scale_w + $scale_h) / 2;
		// calculate $pos_left, $pos_top, $crop_width, $crop_height for fullsize image
		$orig_pos_left		= $pos_left * $scale;
		$orig_pos_top		= $pos_top * $scale;
		$orig_crop_width	= $crop_width * $scale;
		$orig_crop_height	= $crop_height * $scale;
		// Go
		$OBJ = _class("image_manip");
		$thumb_result = $OBJ->crop($original_path, $original_path, $orig_crop_width, $orig_crop_height, $orig_pos_left, $orig_pos_top);
		// Resize all image sizes
		$this->_restore_all_sizes($photo_info);
		// Everything went fine
		return true;
	}

	/**
	* Do rotate photo
	*/
	function _rotate ($photo_info, $angle) {
		$original_path	 	= module('gallery')->_photo_fs_path($photo_info, "original");
		// Go
		$OBJ = _class("image_manip");
		$OBJ->rotate($original_path, $original_path, $angle);
		// Resize all image sizes
		$this->_restore_all_sizes($photo_info);
		// Everything went fine
		return true;
	}

	/**
	* Common ACL checks
	*/
	function _acl_manage_checks ($force_photo_id = 0, $force_user_id = 0) {
		$_GET["id"] = intval($_GET["id"]);
		$PHOTO_ID = $force_photo_id ? $force_photo_id : $_GET["id"];
		// Check if user is member
		if (empty(main()->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		// Ban check
		if (main()->_user_info["ban_images"]) {
			return module('gallery')->_error_msg("ban_images");
		}
		// Try to get given photo info
		$sql = "SELECT * FROM ".db('gallery_photos')." WHERE ";
		if (module('gallery')->HIDE_TOTAL_ID && main()->USER_ID && !$force_photo_id) {
			$sql .= " id2=".intval($PHOTO_ID)." AND user_id=".intval(main()->USER_ID);
		} else {
			$sql .= " id=".intval($PHOTO_ID);
		}
		$photo_info = db()->query_fetch($sql);
		if (empty($photo_info["id"])) {
			return _e("No such photo!");
		}
		// Check owner
		if (MAIN_TYPE_USER && $photo_info["user_id"] != main()->USER_ID) {
			return _e("Not your photo!");
		}

		// Do not hide broken photos from owner
		module('gallery')->SKIP_NOT_FOUND_PHOTOS = false;

		return $photo_info;
	}
}
