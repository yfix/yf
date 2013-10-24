<?php

/**
* Gallery compact view code
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_compact {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->GALLERY_OBJ	= module(GALLERY_CLASS_NAME);
	}
	
	/**
	* Display single photo link (Specially for AJAX)
	*/
	function _compact_view() {
		main()->NO_GRAPHICS = true;
		// Prepare params
		$PHOTO_ID	= intval(substr($_POST["id"], strlen("gallery_")));
		if (empty($PHOTO_ID)) {
			return _e(t("No id!"));
		}
		$PHOTO_TYPE	= "medium";
		// Try to get given photo info
		$photo_info = db()->query_fetch("SELECT * FROM ".db('gallery_photos')." WHERE id=".intval($PHOTO_ID));
		if (empty($photo_info["id"])) {
			return _e(t("No such photo!"));
		}
		// Try to get given user info
		$user_id = $photo_info["user_id"];
		if ($user_id) {
			$user_info = user($user_id, "", array("WHERE" => array("active" => "1")));
		}
		if (empty($user_info)) {
			return _e(t("No such user in database!"));
		}
		if (MAIN_TYPE_USER) {
			$this->GALLERY_OBJ->is_own_gallery = intval(!empty($this->GALLERY_OBJ->USER_ID) && $this->GALLERY_OBJ->USER_ID == $photo_info["user_id"]);
		} elseif (MAIN_TYPE_ADMIN) {
			$this->GALLERY_OBJ->is_own_gallery = true;
		}
		// Get available user folders
		if (empty($this->GALLERY_OBJ->_user_folders_infos)) {
			$this->GALLERY_OBJ->_user_folders_infos = $this->GALLERY_OBJ->_get_user_folders($user_info["id"]);
		}
		// Prepare folder info
		$FOLDER_ID = $photo_info["folder_id"];
		if (empty($FOLDER_ID)) {
			$this->GALLERY_OBJ->_fix_and_get_folder_id($photo_info);
		}
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $this->GALLERY_OBJ->_user_folders_infos[$FOLDER_ID];
		}
		// Folder info is REQUIRED here
		if (empty($cur_folder_info)) {
			return _e(t("Folder info is required"));
		}
		// ###########################
		// Access checks
		// ###########################
		if (!$this->GALLERY_OBJ->is_own_gallery) {
			// Check privacy permissions
			if (!$this->GALLERY_OBJ->_privacy_check($cur_folder_info["privacy"], $photo_info["privacy"], $user_info["id"])) {
				return _e(t("You are not allowed to view this gallery folder"));
			}
			// Check for password for protected gallery
			if (!empty($cur_folder_info["password"])) {
				$PASSWORD_MATCHED = $_SESSION[$this->GALLERY_OBJ->SESSION_PSWD_FIELD][$FOLDER_ID] == $cur_folder_info["password"];
			}
			// Display form to enter the password
			if (!empty($cur_folder_info["password"]) && !$PASSWORD_MATCHED && MAIN_TYPE_USER) {
				return $this->GALLERY_OBJ->_enter_pswd($FOLDER_ID);
			}
		}

// TODO: check if photo exists

		// Prepare other photo info
		$other_info = array();
		if (!empty($photo_info["other_info"])) {
			$other_info = unserialize($photo_info["other_info"]);
		}
		// Check if we need to update other info
		if (empty($other_info[$cur_photo_type]["w"]) || empty($other_info[$cur_photo_type]["h"])) {
			$other_info = $this->GALLERY_OBJ->_update_other_info($photo_info);
		}
		// Prepare real dimensions
		$real_w = $other_info[$PHOTO_TYPE]["w"];
		$real_h = $other_info[$PHOTO_TYPE]["h"];
		$_real_coef = $real_h ? $real_w / $real_h : 0;
		// Limits for the current photo size
		$_max_w = $this->GALLERY_OBJ->PHOTO_TYPES[$PHOTO_TYPE]["max_x"];
		$_max_h = $this->GALLERY_OBJ->PHOTO_TYPES[$PHOTO_TYPE]["max_y"];
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
			$_img_path = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $PHOTO_TYPE);
			common()->make_thumb($_img_path, $_img_path, $_max_w, $_max_h);
			$other_info = $this->GALLERY_OBJ->_update_other_info($photo_info);
		}
		// Prepare template
		$replace = array(
			"photo_id"		=> intval($photo_info["id"]),
			"photo_name"	=> _prepare_html(!empty($photo_info["name"]) ? $photo_info["name"] : _display_name($user_info)." photo"),
			"photo_url"		=> process_url("./?object=gallery&action=show_full_size&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $photo_info["id2"] : $photo_info["id"])),
			"img_src"		=> $this->GALLERY_OBJ->_photo_web_path($photo_info, $PHOTO_TYPE),
			"real_w"		=> intval($real_w),
			"real_h"		=> intval($real_h),
			"user_name"		=> _display_name($user_info),
		);
		$body = tpl()->parse(GALLERY_CLASS_NAME."/compact_view", $replace);

		if (DEBUG_MODE) {
			$body .= "<hr class='clearfloat'>DEBUG INFO:\r\n";
			$body .= common()->_show_execution_time();
//			$body .= common()->show_debug_info();
		}

		echo $body;
	}
}
