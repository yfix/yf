<?php

/**
* Multi Upload image handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_multi_upload_image {

	/** @var array */
	public $ALLOWED_MIME_TYPES = array(
		"image/jpeg"	=> "jpeg",
		"image/pjpeg"	=> "jpeg",
		"image/png"		=> "png",
		"image/gif"		=> "gif",
	);

	/**
	* Do upload image to server
	*/
	function go ($new_file_path, $k , $name_in_form = "image", $max_image_size = 0, $is_local = false) {
		
		// We do not want to user break our operation
		ignore_user_abort(true);
		// New name is required
		
		if (empty($new_file_path)) {
			trigger_error("UPLOAD_IMAGE: New file path id required", E_USER_WARNING);
			return false;
		}
		// Default name in form
		if (empty($name_in_form)) {
			$name_in_form = "image";
		}
		// Prepare params
		// If $name_in_form is an array - then we think that it is $_FILES array with cur image info
		// (useful when uploading several images at once)
		$PHOTO			= is_array($name_in_form) ? $name_in_form : $_FILES[$name_in_form];
		$MAX_IMAGE_SIZE = $max_image_size;
		// Check image size (first attempt)
		if (empty($PHOTO["size"][$k]) || (!empty($MAX_IMAGE_SIZE) && $PHOTO["size"][$k] > $MAX_IMAGE_SIZE)) {
			_re(t("Invalid image size"));
		}
		// First mime type check (quick and simple)
		if ($PHOTO["type"][$k] && !isset($this->ALLOWED_MIME_TYPES[$PHOTO["type"][$k]])) {
			_re(t("Invalid image type"));
		}
		// Check for errors and stop if exists
		if (common()->_error_exists()) {
			return false;
		}
		// Create folder if not exists
		$photo_dir = dirname($new_file_path);
		if (!file_exists($photo_dir)) {
			$DIR_OBJ = _class("dir");
			$DIR_OBJ->mkdir_m($photo_dir, 0777, 1);
		}
		// Upload original photo
		$photo_path = $new_file_path;
		if ($is_local) {
			$move_result = false;
			if (!file_exists($photo_path) && file_exists($PHOTO["tmp_name"][$k])) {
				file_put_contents($photo_path, file_get_contents($PHOTO["tmp_name"][$k]));
				unlink($PHOTO["tmp_name"][$k]);
				$move_result = true;
			}
		} else {
			$move_result = move_uploaded_file($PHOTO["tmp_name"][$k], $photo_path);
		}
		// Check if file uploaded successfully
		if (!$move_result || !file_exists($photo_path) || !filesize($photo_path) || !is_readable($photo_path)) {
			_re("Uploading image error #001. Please <a href='".process_url("./?object=help&action=email_form")."'>contact</a> site admin.");
			trigger_error("Moving uploaded image error", E_USER_WARNING);
			return false;
		}
		// Second image type check (using GD)
		$real_image_info = @getimagesize($photo_path);
		if (empty($real_image_info) || !$real_image_info["mime"] || !isset($this->ALLOWED_MIME_TYPES[$real_image_info["mime"]])) {
			_re(t("Invalid image type"));
			trigger_error("Invalid image type", E_USER_WARNING);
			unlink($photo_path);
			return false;
		}
		$_image_type_short = $this->ALLOWED_MIME_TYPES[$real_image_info["mime"]];
		// Check for wrong photos that crashed GD (only if we do not have NETPBM)
		if ((!defined("NETPBM_PATH") || NETPBM_PATH == "") 
			&& (!defined("IMAGICK_PATH") || IMAGICK_PATH == "")
		) {
			if ($_image_type_short == "jpeg") {
				$c_func = "imagecreatefromjpeg";
			} elseif ($_image_type_short == "png") {
				$c_func = "imagecreatefrompng";
			} elseif ($_image_type_short == "gif") {
				$c_func = "imagecreatefromgif";
			}
			if ($c_func && false === @$c_func($photo_path)) {
				_re("Uploading image error #002. Please <a href='".process_url("./?object=help&action=email_form")."'>contact</a> site admin.");
				trigger_error("Image that crashes GD found", E_USER_WARNING);
				unlink($photo_path);
				return false;
			}
		}
		// Second image size checking (from the real file)
		if (!empty($MAX_IMAGE_SIZE) && filesize($photo_path) > $MAX_IMAGE_SIZE) {
			_re(t("Invalid image size"));
			trigger_error("Image size hacking attempt", E_USER_WARNING);
			unlink($photo_path);
			return false;
		}
		// Third image size checking (force resize it if needed)
		$LIMIT_X = defined("FORCE_RESIZE_WIDTH")	? FORCE_RESIZE_WIDTH	: 1280;
		$LIMIT_Y = defined("FORCE_RESIZE_HEIGHT")	? FORCE_RESIZE_HEIGHT	: 1024;
		if ((defined("FORCE_RESIZE_IMAGE_SIZE") && filesize($photo_path) > FORCE_RESIZE_IMAGE_SIZE)
			|| defined("FORCE_RESIZE_WIDTH")	&& $real_image_info[0] > FORCE_RESIZE_WIDTH
			|| defined("FORCE_RESIZE_HEIGHT")	&& $real_image_info[1] > FORCE_RESIZE_HEIGHT
		) {
			return common()->make_thumb($photo_path, $photo_path, $LIMIT_X, $LIMIT_Y);
		}
		return true;
	}
}
