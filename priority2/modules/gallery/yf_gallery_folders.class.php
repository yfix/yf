<?php

/**
* Gallery virtual folders handler
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_gallery_folders {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->GALLERY_OBJ	= module(GALLERY_CLASS_NAME);
	}

	/**
	* View folder contents
	*/
	function _view_folder () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("Missing folder id!"));
		}
		// Check if such folder exists
		$sql = "SELECT * FROM `".db('gallery_folders')."` WHERE ";
		if ($this->GALLERY_OBJ->HIDE_TOTAL_ID) {
			$sql .= "`id2`=".intval($_GET["id"])." AND `user_id`=".intval($GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : $this->USER_ID);
		} else {
			$sql .= "`id`=".intval($_GET["id"]);
		}
		$cur_folder_info = db()->query_fetch($sql);
		if (empty($cur_folder_info)) {
			return _e(t("No such folder!"));
		}
		$FOLDER_ID	= intval($cur_folder_info["id"]);
		$user_id	= $cur_folder_info["user_id"];
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
			$this->GALLERY_OBJ->is_own_gallery = intval($this->GALLERY_OBJ->USER_ID == $cur_folder_info["user_id"]);
		} elseif (MAIN_TYPE_ADMIN) {
			$this->GALLERY_OBJ->is_own_gallery = true;
		}
		// Output
		return $this->GALLERY_OBJ->_show_user_photos($user_info, $FOLDER_ID, "folder_");
	}

	/**
	* Add new folder
	*/
	function _add_folder () {
		// Check if user is member
		if (empty($this->GALLERY_OBJ->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		// Ban check
		if ($this->GALLERY_OBJ->_user_info["ban_images"]) {
			return $this->GALLERY_OBJ->_error_msg("ban_images");
		}
		// Get current user folders
		$user_folders = $this->GALLERY_OBJ->_get_user_folders($this->GALLERY_OBJ->USER_ID);
		// Check number of user folders
		if (!empty($this->GALLERY_OBJ->MAX_TOTAL_FOLDERS) && count($user_folders) >= $this->GALLERY_OBJ->MAX_TOTAL_FOLDERS) {
			common()->_raise_error("You can create max ".intval($this->GALLERY_OBJ->MAX_TOTAL_FOLDERS)." folders!");
		}
		// Warn user about photos will not displayed in ads
		$WARN_USER = 0;
		// Fix second id
		$_max_folder_id2 = $this->_fix_folder_id2($this->GALLERY_OBJ->USER_ID);
		// Check posted data and save
		if (!empty($_POST["go"])) {
			$_POST["title"]		= substr($_POST["title"], 0, $this->GALLERY_OBJ->MAX_FOLDER_TITLE_LENGTH);
			$_POST["comment"]	= substr($_POST["comment"], 0, $this->GALLERY_OBJ->MAX_FOLDER_COMMENT_LENGTH);
			$_POST["password"]	= substr($_POST["password"], 0, 32);
			// Folder title is required
			if (!strlen($_POST["title"])) {
				common()->_raise_error(t("Folder title is required"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Check text fields
				$_POST["title"]		= $this->GALLERY_OBJ->_filter_text($_POST["title"]);
				$_POST["comment"]	= $this->GALLERY_OBJ->_filter_text($_POST["comment"]);
				// Get time
				$creation_time = time();

				// Generate SQL
				db()->INSERT("gallery_folders", array(
					"user_id"		=> intval($this->GALLERY_OBJ->USER_ID),
					"title"			=> _es($_POST["title"]),
					"comment"		=> _es($_POST["comment"]),
					"content_level"	=> intval($_POST["content_level"]),
					"privacy"		=> intval($_POST["privacy"]),
					"allow_comments"=> intval($_POST["allow_comments"]),
					"password"		=> _es($_POST["password"]),
					"add_date"		=> $creation_time,
					"active" 		=> 1,
					"is_default"	=> 0,
					"id2"			=> intval($_max_folder_id2 + 1),
					"allow_tagging"	=> $_POST["allowed_group"] ? $_POST["allowed_group"] : $this->TAG_OBJ->ALLOWED_GROUP,
				));
				// Get new record id
				$NEW_FOLDER_ID = db()->INSERT_ID();
				// Update public photos
				$this->GALLERY_OBJ->_sync_public_photos();
				// Redirect user
				return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=edit_folder&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? ($_max_folder_id2 + 1) : intval($NEW_FOLDER_ID)). _add_get(array("page")));
			}
			// Update user stats
			main()->call_class_method("user_stats", "classes/", "_update", array("user_id" => $this->GALLERY_OBJ->USER_ID));
		}
		// Fill POST data
		$DATA = $_POST;
		// Show form
		$replace = array(
			"form_action"			=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
			"error_message"			=> _e(),
			"max_title_length"		=> intval($this->GALLERY_OBJ->MAX_FOLDER_TITLE_LENGTH),
			"max_comment_length"	=> intval($this->GALLERY_OBJ->MAX_FOLDER_COMMENT_LENGTH),
			"title"					=> _prepare_html($DATA["title"]),
			"comment"				=> _prepare_html($DATA["comment"]),
			"password"				=> _prepare_html($DATA["password"]),
			"content_level_box"		=> $this->GALLERY_OBJ->_box("content_level",	$DATA["content_level"]),
			"privacy_box"			=> $this->GALLERY_OBJ->_box("privacy",			$DATA["privacy"]),
			"allow_comments_box"	=> $this->GALLERY_OBJ->_box("allow_comments",	$DATA["allow_comments"]),
			"user_id"				=> intval($this->GALLERY_OBJ->USER_ID),
			"back_link"				=> "./?object=".GALLERY_CLASS_NAME."&action=show_gallery"._add_get(array("page")),
			"warn_user"				=> intval($WARN_USER),
			"folder_tagging_box"	=> $this->GALLERY_OBJ->ALLOW_TAGGING ? $this->GALLERY_OBJ->TAG_OBJ->_mod_spec_settings(array("module"=>"gallery")) : "",			
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/add_folder_form", $replace);
	}

	/**
	* Edit folder
	*/
	function _edit_folder () {
		// Check if user is member
		if (empty($this->GALLERY_OBJ->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		// Ban check
		if ($this->GALLERY_OBJ->_user_info["ban_images"]) {
			return $this->GALLERY_OBJ->_error_msg("ban_images");
		}
		// Prepare folder id
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("Missing folder id!"));
		}
		// Fix second id
		$_max_folder_id2 = $this->_fix_folder_id2($this->GALLERY_OBJ->USER_ID);
		// Check if such folder exists
		$sql = "SELECT * FROM `".db('gallery_folders')."` WHERE ";
		if ($this->GALLERY_OBJ->HIDE_TOTAL_ID) {
			$sql .= "`id2`=".intval($_GET["id"])." AND `user_id`=".intval($GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : $this->USER_ID);
		} else {
			$sql .= "`id`=".intval($_GET["id"]);
		}
		$cur_folder_info = db()->query_fetch($sql);
		if (empty($cur_folder_info)) {
			return _e(t("No such folder!"));
		}
		// Fix owner for the admin section
		if (MAIN_TYPE_ADMIN && empty($this->GALLERY_OBJ->USER_ID)) {
			$this->GALLERY_OBJ->USER_ID = $cur_folder_info["user_id"];
		}
		$FOLDER_ID	= intval($cur_folder_info["id"]);
		if ($cur_folder_info["user_id"] != $this->GALLERY_OBJ->USER_ID) {
			return _e(t("Not your folder!"));
		}
		// Warn user about photos will not displayed in ads
		$WARN_USER = 0;
		if ($this->GALLERY_OBJ->WARN_NON_PUBLIC_PHOTOS) {
			// Get number of photos inside this folder that will be displayed inside ads
			$num_photos_in_ads = db()->query_num_rows("SELECT `id` FROM `".db('gallery_photos')."` WHERE `folder_id`=".intval($FOLDER_ID)." AND `show_in_ads`='1'");
			if ($num_photos_in_ads) {
				$WARN_USER = 1;
			}
		}
		// Check posted data and save
		if (!empty($_POST["go"])) {
			$_POST["title"]		= substr($_POST["title"], 0, $this->GALLERY_OBJ->MAX_FOLDER_TITLE_LENGTH);
			$_POST["comment"]	= substr($_POST["comment"], 0, $this->GALLERY_OBJ->MAX_FOLDER_COMMENT_LENGTH);
			$_POST["password"]	= substr($_POST["password"], 0, 32);
			// Folder title is required
			if (!strlen($_POST["title"])) {
				common()->_raise_error(t("Folder title is required"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Unplug photos from the private category from display in ads
				if ($num_photos_in_ads && ($_POST["content_level"] > 1 || $_POST["privacy"] >= 1 || strlen($_POST["password"]))) {
					db()->query("UPDATE `".db('gallery_photos')."` SET `show_in_ads`='0' WHERE `folder_id`=".intval($FOLDER_ID));
				}
				// Check text fields
				$_POST["title"]		= $this->GALLERY_OBJ->_filter_text($_POST["title"]);
				$_POST["comment"]	= $this->GALLERY_OBJ->_filter_text($_POST["comment"]);
				// Get time
				$creation_time = time();
				// Generate SQL
				db()->UPDATE("gallery_folders", array(
					"user_id"		=> intval($this->GALLERY_OBJ->USER_ID),
					"title"			=> _es($_POST["title"]),
					"comment"		=> _es($_POST["comment"]),
					"content_level"	=> intval($_POST["content_level"]),
					"privacy"		=> intval($_POST["privacy"]),
					"allow_comments"=> intval($_POST["allow_comments"]),
					"password"		=> _es($_POST["password"]),
					"active" 		=> 1,
					"allow_tagging"	=> $_POST["allowed_group"] ? $_POST["allowed_group"] : $this->TAG_OBJ->ALLOWED_GROUP,
				), "`id`=".intval($FOLDER_ID));
				// Update public photos
				$this->GALLERY_OBJ->_sync_public_photos();
				// Update user stats
				main()->call_class_method("user_stats", "classes/", "_update", array("user_id" => $this->GALLERY_OBJ->USER_ID));
				// Redirect user
				return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=edit_folder&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : intval($FOLDER_ID)). _add_get(array("page")));
			}
		}
		// Fill POST data
		foreach ((array)$cur_folder_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Show form
		$replace = array(
			"form_action"			=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]."&id=".intval($_GET["id"])._add_get(array("page")),
			"error_message"			=> _e(),
			"max_title_length"		=> intval($this->GALLERY_OBJ->MAX_FOLDER_TITLE_LENGTH),
			"max_comment_length"	=> intval($this->GALLERY_OBJ->MAX_FOLDER_COMMENT_LENGTH),
			"title"					=> _prepare_html($DATA["title"]),
			"comment"				=> _prepare_html($DATA["comment"]),
			"password"				=> _prepare_html($DATA["password"]),
			"content_level_box"		=> $this->GALLERY_OBJ->_box("content_level",	$DATA["content_level"]),
			"privacy_box"			=> $this->GALLERY_OBJ->_box("privacy",			$DATA["privacy"]),
			"allow_comments_box"	=> $this->GALLERY_OBJ->_box("allow_comments",	$DATA["allow_comments"]),
			"user_id"				=> intval($this->GALLERY_OBJ->USER_ID),
			"back_link"				=> "./?object=".GALLERY_CLASS_NAME."&action=view_folder&id=".$_GET["id"]. _add_get(array("page")),
			"is_default"			=> intval((bool)$cur_folder_info["is_default"]),
			"content_level"			=> $this->GALLERY_OBJ->_content_levels[$cur_folder_info["content_level"]],
			"warn_user"				=> intval($WARN_USER),
			"folder_tagging_box"	=> $this->GALLERY_OBJ->ALLOW_TAGGING ? $this->GALLERY_OBJ->TAG_OBJ->_mod_spec_settings(array("module"=>"gallery", "object_id"=>$DATA["id"])) : "",			
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/edit_folder_form", $replace);
	}

	/**
	* Delete folder
	*/
	function _delete_folder () {
		// Check if user is member
		if (empty($this->GALLERY_OBJ->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		// Ban check
		if ($this->GALLERY_OBJ->_user_info["ban_images"]) {
			return $this->GALLERY_OBJ->_error_msg("ban_images");
		}
		// Fix second id
		$_max_folder_id2 = $this->_fix_folder_id2($this->GALLERY_OBJ->USER_ID);
		// Check if such folder exists
		$sql = "SELECT * FROM `".db('gallery_folders')."` WHERE ";
		if ($this->GALLERY_OBJ->HIDE_TOTAL_ID) {
			$sql .= "`id2`=".intval($_GET["id"])." AND `user_id`=".intval($GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : $this->USER_ID);
		} else {
			$sql .= "`id`=".intval($_GET["id"]);
		}
		$cur_folder_info = db()->query_fetch($sql);
		if (empty($cur_folder_info)) {
			return _e(t("No such folder!"));
		}
		// Fix owner for the admin section
		if (MAIN_TYPE_ADMIN && empty($this->GALLERY_OBJ->USER_ID)) {
			$this->GALLERY_OBJ->USER_ID = $cur_folder_info["user_id"];
		}
		$FOLDER_ID	= intval($cur_folder_info["id"]);
		if ($cur_folder_info["user_id"] != $this->GALLERY_OBJ->USER_ID) {
			return _e(t("Not your folder!"));
		}
		// Get current user folders
		$user_folders = $this->GALLERY_OBJ->_get_user_folders($this->GALLERY_OBJ->USER_ID);
		// Get default folder id
		$def_folder_id = $this->_get_def_folder_id($user_folders);
		// Get all photos inside folder
		$Q = db()->query("SELECT * FROM `".db('gallery_photos')."` WHERE `folder_id`=".intval($FOLDER_ID));
		while ($A = db()->fetch_assoc($Q)) {
			$folder_photos[$A["id"]] = $A;
		}
		// Do delete
		if (!empty($_POST)) {
			$NEW_FOLDER_ID = intval($_POST["new_folder_id"]);
			// Check folder owner
			if ($NEW_FOLDER_ID && !isset($user_folders[$NEW_FOLDER_ID])) {
				$NEW_FOLDER_ID = 0;
			}
			// Check if it's last folder
			if (count($user_folders) <= 1) {
				return common()->_raise_error("This is your last folder. You cannot delete it");
			// Folder contains photos
			} elseif (!empty($folder_photos)) {
				// Check required data
				if (empty($_POST["choose"])) {
					common()->_raise_error("Please select action with folder photos: delete or move");
				}
				// Check if we have where to move photos
				if ($_POST["choose"] == "move") {
					if (empty($NEW_FOLDER_ID)) {
						common()->_raise_error("Please select folder to move photos into");
					} elseif ($NEW_FOLDER_ID == $FOLDER_ID) {
						common()->_raise_error("Please select other folder");
					}
				}
			}
			// Check errors
			if (!common()->_error_exists()) {
				// Do delete folder and all its contents
				if ($_POST["choose"] == "delete") {
					// Process all photos inside folder
					foreach ((array)$folder_photos as $photo_info) {
						// Process all types of photos
						foreach ((array)$this->GALLERY_OBJ->PHOTO_TYPES as $format_name => $format_info) {
							$thumb_path = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $format_name);
							if (!file_exists($thumb_path)) {
								continue;
							}
							@unlink($thumb_path);
						}
					}
					// Delete photos from database
					db()->query("DELETE FROM `".db('gallery_photos')."` WHERE `folder_id`=".intval($FOLDER_ID));
				} elseif ($NEW_FOLDER_ID) {
					// Assign default folder id to photos from the deleting folder
					db()->UPDATE("gallery_photos", array(
						"folder_id" => intval($NEW_FOLDER_ID)
					), "`folder_id`=".intval($FOLDER_ID));
				}
				// Change default folder if needed
				if ($FOLDER_ID == $def_folder_id) {
					unset($user_folders[$FOLDER_ID]);
					reset($user_folders);
					$def_folder_id = key($user_folders);
					db()->UPDATE("gallery_folders", array(
						"is_default" => 1
					), "`id`=".intval($def_folder_id));
				}
				// Delete folder record from database
				db()->query("DELETE FROM `".db('gallery_folders')."` WHERE `id`=".intval($FOLDER_ID)." LIMIT 1");
				// Update user stats
				main()->call_class_method("user_stats", "classes/", "_update", array("user_id" => $this->GALLERY_OBJ->USER_ID));
				// Update public photos
				$this->GALLERY_OBJ->_sync_public_photos();
				// Return user back
				return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=show_gallery");
			}
		}
		// Fodlers for select
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			if ($_folder_id == $FOLDER_ID) {
				continue;
			}
			$new_folders[$_folder_id] = _prepare_html($_folder_info["title"]);
		}
		// Display confirmation form
		$replace = array(
			"form_action"		=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]. _add_get(array("page")),
			"back_link"			=> "./?object=".GALLERY_CLASS_NAME."&action=view_folder&id=".$_GET["id"]. _add_get(array("page")),
			"error_message"		=> _e(),
			"folders_box"		=> common()->select_box("new_folder_id", $new_folders, 0, 0, 2, "", false),
			"folder_name"		=> _prepare_html($cur_folder_info["title"]),
			"contains_photos"	=> !empty($folder_photos) ? 1 : 0,
			"is_last_folder"	=> count($user_folders) <= 1 ? 1 : 0,
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/delete_folder", $replace);
	}
	
	/**
	* Fix second id (used for HIDE_TOTAL_ID)
	*/
	function _fix_folder_id2($user_id = 0) {
		if (empty($user_id) || !$this->GALLERY_OBJ->HIDE_TOTAL_ID) {
			return false;
		}
		$_max_folder_id2 = 0;
		// Get all user folders
		$Q = db()->query(
			"SELECT `id`,`id2` FROM `".db('gallery_folders')."` WHERE `user_id`=".intval($user_id)." ORDER BY `id` ASC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$folders[$A["id"]] = $A;
			if ($A["id2"] > $_max_folder_id2) {
				$_max_folder_id2 = $A["id2"];
			}
		}
		$folders_to_update	= array();
		$existed_second_ids	= array();
		// Check duplicates or empty ids
		foreach ((array)$folders as $_folder_id => $_info) {
			if (empty($_info["id2"])) {
				$folders_to_update[$_folder_id] = $_info;
				continue;
			}
			// Duplicate ones
			if (isset($existed_second_ids[$_info["id2"]])) {
				$folders_to_update[$_folder_id] = $_info;
			}
			$existed_second_ids[$_info["id2"]] = $_info["id2"];
		}
		foreach ((array)$folders_to_update as $_folder_id => $_info) {
			$_max_folder_id2++;

			db()->UPDATE("gallery_folders", array(
				"id2" => intval($_max_folder_id2)
			), "`id`=".intval($_folder_id));
		}
		return $_max_folder_id2;
	}

	/**
	* Enter password
	*/
	function _enter_pswd ($FOLDER_ID = 0) {
		// Prepare folder id
		if (empty($FOLDER_ID)) {
			return _e(t("Missing folder id!"));
		}
		// Get current user folder info
		$user_folders = $this->GALLERY_OBJ->_user_folders_infos;
		$cur_folder_info = $user_folders[$FOLDER_ID];
		if (empty($cur_folder_info)) {
			return _e(t("No such folder!"));
		}
		// Check posted password with stored in folder
		if (!empty($_POST)) {
			if (!empty($cur_folder_info["password"]) && $_POST["pswd"] == $cur_folder_info["password"]) {
				$_SESSION[$this->GALLERY_OBJ->SESSION_PSWD_FIELD][$FOLDER_ID] = $cur_folder_info["password"];
			} else {
				common()->_raise_error(t("Wrong password!"));
			}
			// Return user back
			if (!common()->_error_exists()) {
				return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]. (!empty($_GET["id"]) ? "&id=".$_GET["id"] : ""));
			}
		}
		// Display form
		$replace = array(
			"error_message"		=> _e(),
			"enter_pswd_action"	=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]. (!empty($_GET["id"]) ? "&id=".$_GET["id"] : ""),
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/enter_password", $replace);
	}

	/**
	* Get user's available folders
	*/
	function _get_user_folders ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		// Check if it is cached already
		if (isset($GLOBALS['_FOLDERS_CACHE'][$user_id])) {
			return $GLOBALS['_FOLDERS_CACHE'][$user_id];
		} else {
			$GLOBALS['_FOLDERS_CACHE'][$user_id] = array();
		}
		// Get data from db
		$Q = db()->query("SELECT * FROM `".db('gallery_folders')."` WHERE `user_id`=".intval($user_id));
		while ($A = db()->fetch_assoc($Q)) {
			$folders_infos[$A["id"]] = $A;
		}
		// Do create default folder if not exists one
		if (empty($folders_infos)) {
			$info = array(
				"id2"			=> 1,
				"user_id"		=> $user_id,
				"title"			=> $this->GALLERY_OBJ->DEFAULT_FOLDER_NAME,
				"is_default"	=> 1,
				"add_date"		=> time(),
			);
			db()->INSERT("gallery_folders", $info);

			$new_folder_id = db()->INSERT_ID();

			$info["id"] = $new_folder_id;
			$folders_infos[$new_folder_id] = $info;
			// Get default folder id
			if (!empty($new_folder_id)) {
				$def_folder_id = $new_folder_id;
				db()->query("UPDATE `".db('gallery_photos')."` SET `folder_id`=".intval($def_folder_id)." WHERE `user_id`=".intval($user_id));
			}
		}
		// Put info to cache
		$GLOBALS['_FOLDERS_CACHE'][$user_id] = $folders_infos;
		// Return result
		return $folders_infos;
	}

	/**
	* Get users available folders (for many users at one time)
	*/
	function _get_user_folders_for_ids ($users_ids = array()) {
		if (empty($users_ids)) {
			return false;
		}
		$output = array();
		// Check if it is cached already
		foreach ((array)$users_ids as $_user_id) {
	  		if (isset($GLOBALS['_FOLDERS_CACHE'][$_user_id])) {
				$output[$_user_id] = $GLOBALS['_FOLDERS_CACHE'][$_user_id];
				unset($users_ids);
			}
		}
		// Get data from db
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM `".db('gallery_folders')."` WHERE `user_id` IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $folders_infos[$A["user_id"]][$A["id"]] = $A;
		}
		// Process users
		foreach ((array)$users_ids as $_user_id) {
			if (isset($output[$_user_id])) {
				continue;
			}
			// Do create default folders if not exists ones
			if (empty($folders_infos[$_user_id])) {
				$creation_date = time();
				$sql_array = array(
					"id2"			=> 1,
					"user_id"		=> $_user_id,
					"title"			=> $this->GALLERY_OBJ->DEFAULT_FOLDER_NAME,
					"is_default"	=> 1,
					"add_date"		=> $creation_date,
				);
				// Do insert record into db
				db()->INSERT("gallery_folders", $sql_array);
				// Get created record id
				$new_folder_id = db()->INSERT_ID();
				// Prepare output array
				$sql_array["id"] = $new_folder_id;
				$output[$_user_id][$new_folder_id] = $sql_array;
			} else {
				$output[$_user_id] = $folders_infos[$_user_id];
			}
			// Put info to cache
			$GLOBALS['_FOLDERS_CACHE'][$_user_id] = $output[$_user_id];
		}
		// Return result
		return $output;
	}

	/**
	* Get default folder from given user folders array
	*/
	function _get_def_folder_id ($user_folders = array()) {
		if (empty($user_folders) || !is_array($user_folders)) {
			return false;
		}
		// Init with first value
		$def_folder_id = key($user_folders);
		// Try to find default folder
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			if ($_folder_info["is_default"]) {
				$def_folder_id = $_folder_id;
				break;
			}
		}
		return $def_folder_id;
	}
}
