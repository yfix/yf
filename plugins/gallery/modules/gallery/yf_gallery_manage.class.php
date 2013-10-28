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
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->GALLERY_OBJ	= module(GALLERY_CLASS_NAME);

		if ($this->GALLERY_OBJ->ALLOW_TAGGING) {
			$this->TAGS_OBJ = &main()->init_class("tags", "modules/");
		}

	}

	/**
	* Change photo sorting id
	*/
	function _sort_photo () {
		// Second id passed in $_GET["id"] (example: 14_56)
		if (false !== strpos($_GET["id"], "_")) {
			list($_GET["id"], $_second_get_id) = explode("_", $_GET["id"]);
			$_second_get_id = intval($_second_get_id);
		}
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$CUR_ACTION		= strtolower($_GET["page"]);
		$IS_FOR_FOLDER	= substr($CUR_ACTION, -strlen("_in_folder")) == "_in_folder";
		$FIELD_NAME		= $IS_FOR_FOLDER ? "folder_sort_id" : "general_sort_id";
		// Check correct action and go
		if ($CUR_ACTION && in_array($CUR_ACTION, array("up","down","up_in_folder","down_in_folder"))) {
			$CUR_ACTION = str_replace("_in_folder", "", $CUR_ACTION);
			// Here we will apply initial sort numbers
			db()->query(
				"UPDATE ".db('gallery_photos')." SET ".$FIELD_NAME." = id WHERE ".$FIELD_NAME." = 0"
			);
			// Get availiable list of photos where to sort
			$_sort_ids = array();
			$Q = db()->query(
				"SELECT id,".$FIELD_NAME." AS _sort_id 
				FROM ".db('gallery_photos')." 
				WHERE user_id=".intval($this->GALLERY_OBJ->USER_ID)." 
					AND active='1'"
					. ($IS_FOR_FOLDER ? " AND folder_id=".intval($photo_info["folder_id"]) : "")
					. " ORDER BY ".$FIELD_NAME." ASC"
			);
			$_sort_ids = array();
			while ($A = db()->fetch_assoc($Q)) {
				$_sort_ids[$A["id"]] = $A["_sort_id"];
				$_sort_counter[$A["_sort_id"]][$A["id"]] = $A["id"];
			}
			// Fix duplicate sort values
			$_ids_to_update = array();
			foreach ((array)$_sort_counter as $_sort_val => $_ids) {
				if (count($_ids) <= 1) {
					continue;
				}
				// We found several records with same sort id
				foreach ((array)$_ids as $_id_to_update) {
					if (!$_id_to_update) {
						continue;
					}
					$_sort_ids[$_id_to_update] = $_id_to_update;
					$_ids_to_update[$_id_to_update] = $_id_to_update;
				}
			}
			if (!empty($_ids_to_update)) {
				db()->query(
					"UPDATE ".db('gallery_photos')." SET ".$FIELD_NAME." = id WHERE id IN(".implode(",", $_ids_to_update).") AND user_id=".intval($this->GALLERY_OBJ->USER_ID)
				);
			}
			asort($_sort_ids);
			// Try to assign second id passed from GET array
			if ($_second_get_id && isset($_sort_ids[$_second_get_id])) {
				$SECOND_PHOTO_ID = $_second_get_id;
			}
		}
		// Check if we have something to sort here
		if (count($_sort_ids) > 1 && !$SECOND_PHOTO_ID) {
			$_cur_is_first	= key($_sort_ids) == $photo_info["id"];
			$_cur_is_last	= end($_sort_ids) == $photo_info["id"];
			$CUR_SORT_ID = $_sort_ids[$photo_info["id"]];

			if ($CUR_ACTION == "down") {
				foreach ((array)$_sort_ids as $_photo_id => $_sort_id) {
					if ($_photo_id == $photo_info["id"]) {
						continue;
					}
					if ($_sort_id > $CUR_SORT_ID) {
						$SECOND_PHOTO_ID = $_photo_id;
						break;
					}
				}
				if (!$SECOND_PHOTO_ID) {
					if ($_cur_is_first) {
						next($_sort_ids);
						$SECOND_PHOTO_ID = key($_sort_ids);
					} elseif ($_cur_is_last) {
						// Nowhere to down, element is already last in list
					}
				}
			} else {
				foreach ((array)array_reverse($_sort_ids, true) as $_photo_id => $_sort_id) {
					if ($_photo_id == $photo_info["id"]) {
						continue;
					}
					if ($_sort_id < $CUR_SORT_ID) {
						$SECOND_PHOTO_ID = $_photo_id;
						break;
					}
				}
				if (!$SECOND_PHOTO_ID) {
					if ($_cur_is_first) {
						// Nowhere to up, element is already first in list
					} elseif ($_cur_is_last) {
						end($_sort_ids);
						prev($_sort_ids);
						$SECOND_PHOTO_ID = key($_sort_ids);
					}
				}
			}
		}
		// Change order id for these elements
		if (!empty($SECOND_PHOTO_ID)) {
			db()->UPDATE("gallery_photos", array($FIELD_NAME => intval($_sort_ids[$SECOND_PHOTO_ID])), "id=".intval($photo_info["id"]));
			db()->UPDATE("gallery_photos", array($FIELD_NAME => intval($photo_info[$FIELD_NAME])), "id=".intval($SECOND_PHOTO_ID));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo "1";
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}
	
	/**
	* Add Photo
	*/
	function _add_photo($NEW_USER_ID = 0) {
		if (empty($NEW_USER_ID) && !empty($this->GALLERY_OBJ->USER_ID)) {
			$NEW_USER_ID = $this->GALLERY_OBJ->USER_ID;
		}
		// User id is required
		if (empty($NEW_USER_ID)) {
			return false;
		}
		// Check if user is member
		if (empty($this->GALLERY_OBJ->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		// Ban check
		if ($this->GALLERY_OBJ->_user_info["ban_images"] && MAIN_TYPE_USER) {
			return $this->GALLERY_OBJ->_error_msg("ban_images");
		}
		// Prepare folder id
		$FOLDER_ID = intval($_GET["id"]);
		// Get current user folders
		$user_folders = $this->GALLERY_OBJ->_get_user_folders($NEW_USER_ID);
		// Try to find default folder
		$def_folder_id = $this->GALLERY_OBJ->_get_def_folder_id($user_folders);
		// Assign default folder if empty
		if (empty($FOLDER_ID) && !empty($def_folder_id)) {
			$FOLDER_ID = $def_folder_id;
		}
		// Check for folder's owner
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders[$FOLDER_ID];
		}
		if (!empty($cur_folder_info["user_id"]) && $cur_folder_info["user_id"] != $NEW_USER_ID && MAIN_TYPE_USER) {
			return _e(t("Not your folder!"));
		}
		// Prepare folders list for the box
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			$this->GALLERY_OBJ->_folders_for_select[$_folder_id] = _prepare_html($_folder_info["title"]);
		}
		// Prepare show in ads
		$SHOW_IN_ADS_ALLOWED = 0;
		if ($cur_folder_info["content_level"] <= 1 && $cur_folder_info["privacy"] <= 1 && $cur_folder_info["password"] == "") {
			$SHOW_IN_ADS_ALLOWED = 1;
		}
		// Check number of photos to show in ads
		$num_photos_for_ads = db()->query_num_rows(
			"SELECT id FROM ".db('gallery_photos')." WHERE user_id=".intval($NEW_USER_ID)." AND show_in_ads='1'"
		);
		// Fix second id
		$_max_id2 = $this->_fix_id2($NEW_USER_ID);
		// Check posted data and save
		if (!empty($_POST["go"])) {
			$_POST["photo_name"]	= substr($_POST["photo_name"], 0, $this->GALLERY_OBJ->MAX_NAME_LENGTH);
			$_POST["photo_desc"]	= substr($_POST["photo_desc"], 0, $this->GALLERY_OBJ->MAX_DESC_LENGTH);
			$_POST["folder_id"]		= intval($_POST["folder_id"]);
			// Load original photo
			if (empty($_POST["folder_id"]) || !isset($user_folders[$_POST["folder_id"]])) {
				_re(t("Wrong selected folder"));
			}
			// Cleanup wrong or incompleted photos from db
			db()->query(
				"DELETE FROM ".db('gallery_photos')." 
				WHERE user_id=".intval($NEW_USER_ID)." 
					AND active='0'"
			);
			// Check number of user photos
			if (!empty($this->GALLERY_OBJ->MAX_TOTAL_PHOTOS)) {
				$num_photos = db()->query_num_rows("SELECT id FROM ".db('gallery_photos')." WHERE user_id=".intval($NEW_USER_ID));
				if ($num_photos >= $this->GALLERY_OBJ->MAX_TOTAL_PHOTOS) {
					_re(t("You can upload max @num photos!", array("@num" => intval($this->GALLERY_OBJ->MAX_TOTAL_PHOTOS))));
				}
			}
			if ($num_photos_for_ads >= $this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS && $_POST["show_in_ads"] == 1) {
				_re(t("You can use max @num photos in your ads!", array("@num" => intval($this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS))));
			}
			// Shortcut for the uploaded photo info
			$_PHOTO = $_FILES[$this->GALLERY_OBJ->PHOTO_NAME_IN_FORM];
			// Check for photo
			if (empty($_PHOTO) || empty($_PHOTO["size"])) {
				_re(t("Photo file required"));
			}
			// Check for errors and try bulk mode
			if (!common()->_error_exists()) {
				$_source_file_ext = common()->get_file_ext($_PHOTO["name"]);
				if ($this->GALLERY_OBJ->ALLOW_BULK_UPLOAD && strtolower($_source_file_ext) == "zip") {
					return $this->_add_photos_bulk($NEW_USER_ID);
				}
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Check text fields
				$_POST["photo_name"] = $this->GALLERY_OBJ->_filter_text($_POST["photo_name"]);
				$_POST["photo_desc"] = $this->GALLERY_OBJ->_filter_text($_POST["photo_desc"]);
				// Prepare source file photo name
				$SOURCE_PHOTO_NAME = $this->_prepare_photo_name($_PHOTO["name"]);
				// Get time
				$creation_time = time();
				// Begin transaction
				db()->query("BEGIN");
				// Generate SQL
				$sql_array = array(
					"user_id"		=> intval($NEW_USER_ID),
					"folder_id"		=> intval($_POST["folder_id"]),
					"img_name"		=> _es($SOURCE_PHOTO_NAME),
					"name"			=> _es($_POST["photo_name"]),
					"desc"			=> _es($_POST["photo_desc"]),
					"add_date"		=> $creation_time,
					"active" 		=> 0,
					"show_in_ads"	=> intval((bool) $_POST["show_in_ads"]),
					"allow_rate"	=> intval((bool) $_POST["allow_rate"]),
					"allow_tagging"	=> intval((bool) $_POST["allow_tagging"]),
					"id2"			=> intval($_max_id2 + 1),
					"is_featured"	=> intval((bool) $_POST["is_featured"]),
				);
				db()->INSERT("gallery_photos", $sql_array);
				// Get new record id
				$PHOTO_RECORD_ID = intval(db()->INSERT_ID());
				if (empty($PHOTO_RECORD_ID)) {
					_re(t("Cant insert record into db"));
				}
				// Save tags 
				if (isset($_POST["tags"])) {
					$this->TAGS_OBJ->_save_tags($_POST["tags"], $PHOTO_RECORD_ID, GALLERY_CLASS_NAME);
				}

			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Create new photo name (using name template)
				$new_photo_info = array(
					"id"		=> $PHOTO_RECORD_ID,
					"id2"		=> intval($_max_id2 + 1),
					"user_id"	=> $NEW_USER_ID,
					"folder_id"	=> $_POST["folder_id"],
					"add_date"	=> $creation_time,
				);
				$load_result = $this->_load_photo($_PHOTO, $new_photo_info);
				// Roll back uploaded photos
				if (!$load_result) {
					$this->_load_photo_rollback($new_photo_info);
				} else {
					// Update "other_info"
					$this->GALLERY_OBJ->_update_other_info($new_photo_info);
				}
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Set db record active
				db()->UPDATE("gallery_photos", array(
					"active"	=> 1,
				), "id=".intval($PHOTO_RECORD_ID));
			} 
			// Redirect user
			if (!common()->_error_exists()) {
				// Commit transaction
				db()->query("COMMIT");
				// Update public photos
				$this->GALLERY_OBJ->_sync_public_photos($this->GALLERY_OBJ->USER_ID);
				// Update user stats
				_class_safe("user_stats")->_update(array("user_id" => $NEW_USER_ID));

				$redirect_folder_id = $this->GALLERY_OBJ->HIDE_TOTAL_ID ? $user_folders[$_POST["folder_id"]]["id2"] : $_POST["folder_id"];

				return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=".(!empty($redirect_folder_id) ? "view_folder&id=".$redirect_folder_id : "show_gallery"). _add_get(array("page")));
			}
		}
		if (common()->_error_exists()) {
			$error_message = _e();
			// Roll back transaction
			db()->query("ROLLBACK");
		}
		$allow_edit_tags = $this->GALLERY_OBJ->ALLOW_TAGGING ? true : false;
		// Show form
		$replace = array(
			"form_action"		=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
			"error_message"		=> $error_message,
			"folders_box"		=> $this->GALLERY_OBJ->_box("folder_id", !empty($_POST["folder_id"]) ? $_POST["folder_id"] : $FOLDER_ID),
			"show_in_ads_box"	=> $SHOW_IN_ADS_ALLOWED ? $this->GALLERY_OBJ->_box("show_in_ads", $_POST["show_in_ads"] || $num_photos_for_ads < $this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS ? 1 : 0) : "",
			"max_image_size"	=> intval($this->GALLERY_OBJ->MAX_IMAGE_SIZE),
			"max_name_length"	=> intval($this->GALLERY_OBJ->MAX_NAME_LENGTH),
			"max_desc_length"	=> intval($this->GALLERY_OBJ->MAX_DESC_LENGTH),
			"photo_name"		=> _prepare_html($_POST["photo_name"]),
			"photo_desc"		=> _prepare_html($_POST["photo_desc"]),
			"user_id"			=> intval($NEW_USER_ID),
			"show_ads_denied"	=> intval(!$SHOW_IN_ADS_ALLOWED),
			"rate_enabled"		=> intval((bool) $this->GALLERY_OBJ->ALLOW_RATE),
			"tagging_enabled"	=> intval((bool) $this->GALLERY_OBJ->ALLOW_TAGGING),
			"allow_rate_box"	=> $this->GALLERY_OBJ->_box("allow_rate", $_POST["allow_rate"] || $this->GALLERY_OBJ->ALLOW_RATE ? 1 : 0),
			"allow_tagging_box"	=> $this->GALLERY_OBJ->_box("allow_tagging", $_POST["allow_tagging"] || $this->GALLERY_OBJ->ALLOW_TAGGING ? 1 : 0),
			"tags"				=> "",
			"max_num_tags"		=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->TAGS_PER_OBJ : "",
			"min_tag_len"		=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->MIN_KEYWORD_LENGTH : "",
			"max_tag_len"		=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->MAX_KEYWORD_LENGTH : "",
			"is_featured_box"	=> $this->GALLERY_OBJ->_box("is_featured", $photo_info["is_featured"]),
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/add_photo_form", $replace);
	}
	
	/**
	* Add photos in bulk mode (using zip archive with photos)
	*/
	function _add_photos_bulk($NEW_USER_ID = 0) {
		if (!$this->GALLERY_OBJ->ALLOW_BULK_UPLOAD || !$NEW_USER_ID) {
			return false;
		}
		// Do not allow to upload once a time more photos than this num
		$ADD_PHOTOS_ALLOWED_NUM = 50;
		// Count number of allowed photos to upload
		if (!empty($this->GALLERY_OBJ->MAX_TOTAL_PHOTOS)) {
			$num_photos = db()->query_num_rows("SELECT id FROM ".db('gallery_photos')." WHERE user_id=".intval($NEW_USER_ID));
			if ($num_photos >= $this->GALLERY_OBJ->MAX_TOTAL_PHOTOS) {
				return _e(t("You can upload max @num photos!", array("@num" => intval($this->GALLERY_OBJ->MAX_TOTAL_PHOTOS))));
			} else {
				$ADD_PHOTOS_ALLOWED_NUM = $this->GALLERY_OBJ->MAX_TOTAL_PHOTOS - $num_photos;
			}
		}
		// Extract archive
		$_ARCHIVE = $_FILES[$this->GALLERY_OBJ->PHOTO_NAME_IN_FORM];
		$_tmp_dir = INCLUDE_PATH."uploads/tmp/";
		if (!file_exists($_temp_dir)) {
			_mkdir_m($_tmp_dir);
		}
		$_tmp_name = time()."_".abs(crc32(microtime(true).$_ARCHIVE["name"]));
		$_archive_uploaded_path = $_tmp_dir.$_tmp_name.".zip";
		$_archive_extract_path	= $_tmp_dir.$_tmp_name."/";
		if (!move_uploaded_file($_ARCHIVE["tmp_name"], $_archive_uploaded_path)) {
			return _e("GALLERY: upload internal error #1 in ".__FUNCTION__);
		}
		// Init zip object
		main()->load_class_file("pclzip", "classes/");
		if (class_exists("pclzip")) {
			$this->ZIP_OBJ = new pclzip($_archive_uploaded_path);
		}
		// Check if library loaded
		if (!is_object($this->ZIP_OBJ)) {
			trigger_error("GALLERY: Cant init PclZip module", E_USER_ERROR);
			return _e("GALLERY: upload internal error #2 in ".__FUNCTION__);
		}
		$result = $this->ZIP_OBJ->extract(PCLZIP_OPT_PATH, $_archive_extract_path);
		// Check for extraction errors
		if (!$result) {
			return _e("GALLERY: upload internal error #3 in ".__FUNCTION__);
		}
		$DIR_OBJ = main()->init_class("dir", "classes/");

		// Get photos availiable to process
		$photos = $DIR_OBJ->scan_dir($_archive_extract_path, true, array("", "/\.(jpg|jpeg|gif|png)\$/"), "/(svn|git)/");
		$photos = array_slice((array)$photos, -abs($ADD_PHOTOS_ALLOWED_NUM));


		// Check text fields
		$_POST["photo_name"] = $this->GALLERY_OBJ->_filter_text($_POST["photo_name"]);
		$_POST["photo_desc"] = $this->GALLERY_OBJ->_filter_text($_POST["photo_desc"]);
		// Get time
		$creation_time = time();
		// Fix second id
		$_max_id2 = $this->_fix_id2($NEW_USER_ID);


		// Do process them!
		foreach ((array)$photos as $_photo_path) {
			if (common()->_error_exists()) {
				break;
			}
			// Prepare source file photo name
			$SOURCE_PHOTO_NAME = $this->_prepare_photo_name(basename($_photo_path));
			// Begin transaction
			db()->query("BEGIN");
			// Generate SQL
			$sql_array = array(
				"user_id"		=> intval($NEW_USER_ID),
				"folder_id"		=> intval($_POST["folder_id"]),
				"img_name"		=> _es($SOURCE_PHOTO_NAME),
				"name"			=> _es($_POST["photo_name"]),
				"desc"			=> _es($_POST["photo_desc"]),
				"add_date"		=> $creation_time,
				"active" 		=> 0,
				"show_in_ads"	=> 0,
				"allow_rate"	=> intval((bool) $_POST["allow_rate"]),
				"allow_tagging"	=> intval((bool) $_POST["allow_tagging"]),
				"id2"			=> intval($_max_id2 + 1),
				"is_featured"	=> intval((bool) $_POST["is_featured"]),
			);
			db()->INSERT("gallery_photos", $sql_array);
			// Get new record id
			$PHOTO_RECORD_ID = intval(db()->INSERT_ID());
			if (empty($PHOTO_RECORD_ID)) {
				_re(t("Cant insert record into db"));
			}
			// Save tags 
			if (isset($_POST["tags"])) {
				$this->TAGS_OBJ->_save_tags($_POST["tags"], $PHOTO_RECORD_ID, GALLERY_CLASS_NAME);
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Create new photo name (using name template)
				$new_photo_info = array(
					"id"		=> $PHOTO_RECORD_ID,
					"id2"		=> intval($_max_id2 + 1),
					"user_id"	=> $NEW_USER_ID,
					"folder_id"	=> $_POST["folder_id"],
					"add_date"	=> $creation_time,
				);
				$load_result = $this->_load_photo(array(
					"name"		=> $SOURCE_PHOTO_NAME,
					"type"		=> "",
					"tmp_name"	=> $_photo_path,
					"error"		=> 0,
					"size"		=> @filesize($_photo_path),
				), $new_photo_info, true);
				// Roll back uploaded photos
				if (!$load_result) {
					$this->_load_photo_rollback($new_photo_info);
				} else {
					// Update "other_info"
					$this->GALLERY_OBJ->_update_other_info($new_photo_info);
				}
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Set db record active
				db()->UPDATE("gallery_photos", array(
					"active"	=> 1,
				), "id=".intval($PHOTO_RECORD_ID));
				// Commit transaction
				db()->query("COMMIT");
			} else {
				// Roll back transaction
				db()->query("ROLLBACK");
			}
			// !! important !!
			$_max_id2++;
		}

		// Sync is here
		if (!common()->_error_exists()) {

			$this->GALLERY_OBJ->_sync_public_photos($NEW_USER_ID);

			_class_safe("user_stats")->_update(array("user_id" => $NEW_USER_ID));
		}

		// Cleanup
		$DIR_OBJ->delete_dir($_archive_extract_path, true);
		unlink($_archive_uploaded_path);

		if (common()->_error_exists()) {
			return _e();
		}

		$redirect_folder_id = $this->GALLERY_OBJ->HIDE_TOTAL_ID ? $user_folders[$_POST["folder_id"]]["id2"] : $_POST["folder_id"];

		return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=".(!empty($redirect_folder_id) ? "view_folder&id=".$redirect_folder_id : "show_gallery"). _add_get(array("page")));
	}
	
	/**
	* Edit Photo
	*/
	function _edit_photo() {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		// Prepare folder id
		$FOLDER_ID = intval($photo_info["folder_id"]);
		// Get current user folders
		$user_folders = $this->GALLERY_OBJ->_get_user_folders($photo_info["user_id"]);
		// Try to find default folder
		$def_folder_id = $this->GALLERY_OBJ->_get_def_folder_id($user_folders);
		// Do set default folder for photo with empty folder field
		if (empty($FOLDER_ID)) {
			// Do update record
			db()->UPDATE("gallery_photos", array(
				"folder_id"	=> intval($def_folder_id),
			), "id=".intval($photo_info["id"]));
		}
		// Check for folder's owner
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders[$FOLDER_ID];
		}
		// Prepare folders list for the box
		foreach ((array)$user_folders as $_folder_id => $_folder_info) {
			$this->GALLERY_OBJ->_folders_for_select[$_folder_id] = _prepare_html($_folder_info["title"]);
		}
		// Prepare show in ads
		$SHOW_IN_ADS_ALLOWED = 0;
		if ($cur_folder_info["content_level"] <= 1 && $cur_folder_info["privacy"] <= 1 && $cur_folder_info["password"] == "") {
			$SHOW_IN_ADS_ALLOWED = 1;
		}
		// Check number of photos to show in ads
		$num_photos_for_ads = db()->query_num_rows(
			"SELECT id FROM ".db('gallery_photos')." WHERE user_id=".intval($this->GALLERY_OBJ->USER_ID)." AND show_in_ads='1'"
		);
		// Fix second id
		$_max_id2 = $this->_fix_id2($photo_info["user_id"]);
		// Check posted data and save
		if (!empty($_POST["go"])) {
			// Save tags 
			if (isset($_POST["tags"])) {
				$this->TAGS_OBJ->_save_tags($_POST["tags"], $photo_info["id"], GALLERY_CLASS_NAME);
			}
			$_POST["photo_name"]	= substr($_POST["photo_name"], 0, $this->GALLERY_OBJ->MAX_NAME_LENGTH);
			$_POST["photo_desc"]	= substr($_POST["photo_desc"], 0, $this->GALLERY_OBJ->MAX_DESC_LENGTH);
			$_POST["folder_id"]		= intval($_POST["folder_id"]);
			// Load original photo
			if (empty($_POST["folder_id"]) || !isset($user_folders[$_POST["folder_id"]])) {
				_re(t("Wrong selected folder"));
			}
			// Check number of photos to show in ads
			if ($num_photos_for_ads >= $this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS && $_POST["show_in_ads"] == 1) {
				_re(t("You can use max @num photos in your ads!", array("@num" => intval($this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS))));
			}
			// Shortcut for the uploaded photo info
			$_PHOTO = $_FILES[$this->GALLERY_OBJ->PHOTO_NAME_IN_FORM];
			// Check for errors
			if (!common()->_error_exists()) {
				// Check text fields
				$_POST["photo_name"] = $this->GALLERY_OBJ->_filter_text($_POST["photo_name"]);
				$_POST["photo_desc"] = $this->GALLERY_OBJ->_filter_text($_POST["photo_desc"]);
				// Prepare source file photo name
				$SOURCE_PHOTO_NAME = $this->_prepare_photo_name($_PHOTO["name"]);
				// Begin transaction
				db()->query("BEGIN");
				// Generate SQL
				$sql_array = array(
					"folder_id"		=> intval($_POST["folder_id"]),
					"name"			=> _es($_POST["photo_name"]),
					"desc"			=> _es($_POST["photo_desc"]),
					"show_in_ads"	=> intval((bool) $_POST["show_in_ads"]),
					"allow_rate"	=> intval((bool) $_POST["allow_rate"]),
					"allow_tagging"	=> intval((bool) $_POST["allow_tagging"]),
					"is_featured"	=> intval((bool) $_POST["is_featured"]),
				);
				if (!empty($_PHOTO["size"])) {
					$sql_array["img_name"]	= _es($SOURCE_PHOTO_NAME);
				}
				db()->UPDATE("gallery_photos", $sql_array, "id=".intval($photo_info["id"]));
			}
			// Check for errors
			if (!common()->_error_exists() && !empty($_PHOTO["size"])) {
				// Create new photo name (using name template)
				$new_photo_info = array(
					"id"		=> $photo_info["id"],
					"id2"		=> $photo_info["id2"],
					"user_id"	=> $photo_info["user_id"],
					"folder_id"	=> $_POST["folder_id"],
					"add_date"	=> $photo_info["add_date"],
				);
				$load_result = $this->_load_photo($_PHOTO, $new_photo_info);
				// Roll back uploaded photos
				if (!$load_result) {
					$this->_load_photo_rollback($new_photo_info);
				} else {
					// Update "other_info"
					$this->GALLERY_OBJ->_update_other_info($new_photo_info);
				}
				$_SESSION["_refresh_image_in_browser"] = true;
			}
			// Redirect user
			if (!common()->_error_exists()) {
				// Commit transaction
				db()->query("COMMIT");
				// Update public photos
				$this->GALLERY_OBJ->_sync_public_photos($this->GALLERY_OBJ->USER_ID);

				$redirect_folder_id = $this->GALLERY_OBJ->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"];
				// Changed folder
				if ($_POST["folder_id"] && $_POST["folder_id"] != $cur_folder_info["id"]) {
					$redirect_folder_id = $this->GALLERY_OBJ->HIDE_TOTAL_ID ? $user_folders[$_POST["folder_id"]]["id2"] : $_POST["folder_id"];
				}

				return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=".(!empty($redirect_folder_id) ? "view_folder&id=".$redirect_folder_id : "show_gallery")._add_get(array("page")));
			}
		} else {
			$_POST["photo_name"]	= $photo_info["name"];
			$_POST["photo_desc"]	= $photo_info["desc"];
			$_POST["folder_id"]		= $photo_info["folder_id"];
			$_POST["show_in_ads"]	= $photo_info["show_in_ads"];
		}
		if (common()->_error_exists()) {
			$error_message = _e();
			// Roll back transaction
			db()->query("ROLLBACK");
		}
		// Prepare tags array
		if ($this->GALLERY_OBJ->ALLOW_TAGGING) {
			$_prefetched_tags = $this->GALLERY_OBJ->_get_tags($photo_info["id"]);
			foreach ((array)$GLOBALS['_gallery_tags'][$photo_info["id"]] as $_name) {
				$tags[$_name] = "./?object=".GALLERY_CLASS_NAME."&action=tag&id=".urlencode($_name);
			}
		}
		$allow_edit_tags = $this->GALLERY_OBJ->ALLOW_TAGGING ? true : false;
		// Important!
		$this->_cur_rand = microtime(true);
		// Show form
		$cur_photo_type = "thumbnail";
		$_fs_thumb_src = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $cur_photo_type);
		$thumb_web_path = "";
		if (file_exists($_fs_thumb_src)) {
			$thumb_web_path = $this->GALLERY_OBJ->_photo_web_path($photo_info, $cur_photo_type);
		}
		if ($_SESSION["_refresh_image_in_browser"]) {
			$thumb_web_path .= "?".$this->_cur_rand;
		}
		$replace = array(
			"form_action"		=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
			"error_message"		=> $error_message,
			"folders_box"		=> $this->GALLERY_OBJ->_box("folder_id", !empty($_POST["folder_id"]) ? $_POST["folder_id"] : $FOLDER_ID),
			"show_in_ads_box"	=> $SHOW_IN_ADS_ALLOWED ? $this->GALLERY_OBJ->_box("show_in_ads", $num_photos_for_ads >= $this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS ? 0 : $_POST["show_in_ads"]) : "",
			"max_image_size"	=> intval($this->GALLERY_OBJ->MAX_IMAGE_SIZE),
			"max_name_length"	=> intval($this->GALLERY_OBJ->MAX_NAME_LENGTH),
			"max_desc_length"	=> intval($this->GALLERY_OBJ->MAX_DESC_LENGTH),
			"photo_name"		=> _prepare_html($_POST["photo_name"]),
			"photo_desc"		=> _prepare_html($_POST["photo_desc"]),
			"thumb_src"			=> $thumb_web_path,
			"user_id"			=> intval($this->GALLERY_OBJ->USER_ID),
			"show_ads_denied"	=> intval(!$SHOW_IN_ADS_ALLOWED),
			"crop_link"			=> "./?object=".GALLERY_CLASS_NAME."&action=crop_photo&id=".$_GET["id"]._add_get(array("page")),
			"rotate_link"		=> "./?object=".GALLERY_CLASS_NAME."&action=rotate_photo&id=".$_GET["id"]._add_get(array("page")),
			"back_link"			=> "./?object=".GALLERY_CLASS_NAME."&action=".(!empty($photo_info["folder_id"]) ? "view_folder&id=".$photo_info["folder_id"] : "show_gallery")._add_get(array("page")),
			"refresh_image_code"=> $this->_refresh_images_in_browser($photo_info["id"]),
			"rate_enabled"		=> intval((bool) $this->GALLERY_OBJ->ALLOW_RATE),
			"rating"			=> round($photo_info["rating"], 1),
			"rate_num_votes"	=> intval($photo_info["num_votes"]),
			"rate_last_voted"	=> _format_date($photo_info["last_vote_date"]),
			"tagging_enabled"	=> intval((bool) $this->GALLERY_OBJ->ALLOW_TAGGING),
			"tags"				=> $this->GALLERY_OBJ->ALLOW_TAGGING && !empty($tags) ? $tags : "",
			"edit_tags_link"	=> $allow_edit_tags ? process_url("./?object=".GALLERY_CLASS_NAME."&action=edit_tags_popup&id=".$photo_info["id"]._add_get(array("page"))) : "",
			"allow_rate_box"	=> $this->GALLERY_OBJ->_box("allow_rate", $photo_info["allow_rate"]),
			"allow_tagging_box"	=> $this->GALLERY_OBJ->_box("allow_tagging", $photo_info["allow_tagging"]),
			"edit_folder_link"	=> "./?object=".GALLERY_CLASS_NAME."&action=edit_folder&id=".intval($FOLDER_ID),
			"tags"				=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->_collect_tags($photo_info["id"], GALLERY_CLASS_NAME) : "",
			"max_num_tags"		=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->TAGS_PER_OBJ : "",
			"min_tag_len"		=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->MIN_KEYWORD_LENGTH : "",
			"max_tag_len"		=> is_object($this->TAGS_OBJ) ? $this->TAGS_OBJ->MAX_KEYWORD_LENGTH : "",
			"is_featured_box"	=> $this->GALLERY_OBJ->_box("is_featured", $photo_info["is_featured"]),
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/edit_photo_form", $replace);
	}
	
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
				"user_id"	=> $this->GALLERY_OBJ->USER_ID,
			);
			foreach ((array)$this->GALLERY_OBJ->PHOTO_TYPES as $format_name => $format_info) {
				if ($format_name == "original") {
					continue;
				}
				$_images[] = $this->GALLERY_OBJ->_photo_web_path($photo_info, $format_name);
			}
		}
		// Avatar
		if ($_SESSION["_refresh_avatar_in_browser"]) {
			// Prevent double execution
			unset($_SESSION["_refresh_avatar_in_browser"]);

			$_images[] = SITE_AVATARS_DIR. _gen_dir_path($this->GALLERY_OBJ->USER_ID). intval($this->GALLERY_OBJ->USER_ID). ".jpg";
		}
		$body .= "";
		if (!empty($_images)) {
			foreach ((array)$_images as $_src) {
				$body .= "<img src='".$_src ."?".$this->_cur_rand."' width='1' height='1' style='visibility:hidden;'>";
			}
			$body = "<span style='width:0px;height:0px;'>".$body."</span>";
		}
		return $body;
	}
	
	/**
	* Fix second id (used for HIDE_TOTAL_ID)
	*/
	function _fix_id2($user_id = 0) {
		if (empty($user_id) || !$this->GALLERY_OBJ->HIDE_TOTAL_ID) {
			return false;
		}
		$_max_id2++;
		// Get all user photos
		$Q = db()->query(
			"SELECT id,id2 FROM ".db('gallery_photos')." WHERE user_id=".intval($user_id)." ORDER BY id ASC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$photos[$A["id"]] = $A["id2"];
			if ($A["id2"] > $_max_id2) {
				$_max_id2 = $A["id2"];
			}
		}
		$photos_to_update	= array();
		$existed_second_ids = array();
		// Check duplicates or empty ids
		foreach ((array)$photos as $_photos_id => $_info) {
			if (empty($_info["id2"])) {
				$photos_to_update[$_photos_id] = $_info;
				continue;
			}
			// Duplicate ones
			if (isset($existed_second_ids[$_info["id2"]])) {
				$photos_to_update[$_photo_id] = $_info;
			}
			$existed_second_ids[$_info["id2"]] = $_info["id2"];
		}
		foreach ((array)$photos_to_update as $_photo_id => $_photo_info) {
			$_max_id2++;

			db()->UPDATE("gallery_photos", array(
				"id2" => intval($_max_id2)
			), "id=".intval($_photo_id));
		}
		// Fix folders
		$FOLDERS_OBJ = $this->GALLERY_OBJ->_load_sub_module("gallery_folders");
		$FOLDERS_OBJ->_fix_folder_id2($user_id);

		return $_max_id2;
	}
	
	/**
	* Delete Photo
	*/
	function _delete_photo($FORCE_PHOTO_ID = 0) {
		$photo_info = $this->_acl_manage_checks($FORCE_PHOTO_ID);
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$cur_folder_info = $this->_get_photo_folder_info($photo_info);
		// Process all types of photos
		foreach ((array)$this->GALLERY_OBJ->PHOTO_TYPES as $format_name => $format_info) {
			$thumb_path = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $format_name);
			if (!file_exists($thumb_path)) {
				continue;
			}
			@unlink($thumb_path);
		}
		// Delete from database
		db()->query("DELETE FROM ".db('gallery_photos')." WHERE id=".intval($photo_info["id"])." LIMIT 1");
		// Update public photos
		$this->GALLERY_OBJ->_sync_public_photos();
		// Update user stats
		_class_safe("user_stats")->_update(array("user_id" => $this->GALLERY_OBJ->USER_ID));
		// Redirect user
		return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=".(!empty($photo_info["folder_id"]) ? "view_folder&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]) : "show_gallery")._add_get(array("page")));
	}

	/**
	* Folder info
	*/
	function _get_photo_folder_info ($photo_info = array(), $FOLDER_ID = 0) {
		// Get current user folders
		$user_folders = $this->GALLERY_OBJ->_get_user_folders(main()->USER_ID);
		// Try to find default folder
		$def_folder_id = $this->GALLERY_OBJ->_get_def_folder_id($user_folders);
		// Assign default folder if empty
		if (empty($FOLDER_ID) && !empty($def_folder_id)) {
			$FOLDER_ID = $def_folder_id;
		}
		// Check for folder's owner
		if (!empty($FOLDER_ID)) {
			$cur_folder_info = $user_folders[$FOLDER_ID];
		}
		return $cur_folder_info;
	}
	
	/**
	* Image cropper
	*/
	function _crop_photo() {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		if (!$this->GALLERY_OBJ->ALLOW_IMAGE_MANIPULATIONS) {
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
			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=edit_photo&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $photo_info["id2"] : $photo_info["id"]). _add_get(array("page")));
		}
		// Show form
		$_fs_thumb_src = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $cur_photo_type);
		$thumb_web_path = "";
		if (file_exists($_fs_thumb_src)) {
			$thumb_web_path = $this->GALLERY_OBJ->_photo_web_path($photo_info, $cur_photo_type);
		}
		$replace = array(
			"form_action"		=> "./?object=".GALLERY_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
			"error_message"		=> _e(),
			"photo_name"		=> _prepare_html($_POST["photo_name"]),
			"photo_desc"		=> _prepare_html($_POST["photo_desc"]),
			"thumb_src"			=> $thumb_web_path,
			"user_id"			=> intval($this->GALLERY_OBJ->USER_ID),
			"back_link"			=> "./?object=".GALLERY_CLASS_NAME."&action=edit_photo&id=".$photo_info["id"]._add_get(array("page")),
			"real_w"			=> intval($real_w),
			"real_h"			=> intval($real_h),
		);
		return tpl()->parse(GALLERY_CLASS_NAME."/crop_photo_form", $replace);
	}
	
	/**
	* Image rotater
	*/
	function _rotate_photo() {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		if (!$this->GALLERY_OBJ->ALLOW_IMAGE_MANIPULATIONS) {
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

			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=edit_photo&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $photo_info["id2"] : $photo_info["id"]). _add_get(array("page")));
		}
	}

	/**
	* Change show in ads status
	*/
	function _change_show_ads () {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		$cur_folder_info = $this->_get_photo_folder_info($photo_info);
		// Check number of photos to show in ads
		$num_photos_for_ads = db()->query_num_rows("SELECT id FROM ".db('gallery_photos')." WHERE user_id=".intval($this->GALLERY_OBJ->USER_ID)." AND show_in_ads='1'");
		if ($num_photos_for_ads >= $this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS && $photo_info["show_in_ads"] == 0) {
			_re(t("You can use max @num photos in your ads!", array("@num" => intval($this->GALLERY_OBJ->MAX_PHOTOS_FOR_ADS))));
			return redirect("./?object=".GALLERY_CLASS_NAME."&action=show_gallery"._add_get(array("page")), 1, _e());
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
			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=view_folder&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]));
		}
	}

	/**
	* Change rate if allowed
	*/
	function _change_rate_allowed () {
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
			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=view_folder&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]));
		}
	}

	/**
	* Change allow_tagging
	*/
	function _change_tagging_allowed () {
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
			return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=view_folder&id=".($this->GALLERY_OBJ->HIDE_TOTAL_ID ? $cur_folder_info["id2"] : $cur_folder_info["id"]));
		}
	}

	/**
	* Make given photo default
	*/
	function _make_default () {
		$photo_info = $this->_acl_manage_checks();
		if (!is_array($photo_info)) {
			return $photo_info; // error string
		}
		// Create paths
		$thumb_path_1	= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "thumbnail");
		$thumb_path_2	= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "medium");
		$avatar_path_1	= INCLUDE_PATH. SITE_AVATARS_DIR. intval($this->GALLERY_OBJ->USER_ID). $this->GALLERY_OBJ->IMAGE_EXT;
		$avatar_path_2	= INCLUDE_PATH. SITE_AVATARS_DIR. intval($this->GALLERY_OBJ->USER_ID). "_m". $this->GALLERY_OBJ->IMAGE_EXT;
		// Copy thumb to the avatars folder
		// Hm... strange, but this case is most stable and work in most cases (instead of "rename" or "copy")
		file_put_contents($avatar_path_1, file_get_contents($thumb_path_1));
		file_put_contents($avatar_path_2, file_get_contents($thumb_path_2));

		$_SESSION["_refresh_avatar_in_browser"] = true;

		// Return user back
		return js_redirect("./?object=".GALLERY_CLASS_NAME."&action=edit");
	}

	/**
	* Load photo
	*/
	function _load_photo ($_PHOTO = array(), $photo_info = array(), $is_local = false) {
		if (empty($_PHOTO) || empty($photo_info)) {
			return false;
		}
		$photo_path		= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "original");
		$photo_dir		= dirname($photo_path)."/";
		$new_file_name	= basename($photo_path);
		// Do upload image
		$upload_result = common()->upload_image($photo_path, $_PHOTO, $this->GALLERY_OBJ->MAX_IMAGE_SIZE, $is_local);
		if (!$upload_result) {
			if (!common()->_error_exists()) {
				_e(t("Unrecognized error occured while uploading image"));
			}
			return false;
		}
		// Fix original image size (if needed)
		$orig_max_x = $this->GALLERY_OBJ->PHOTO_TYPES["original"]["max_x"];
		$orig_max_y = $this->GALLERY_OBJ->PHOTO_TYPES["original"]["max_y"];
		if (!empty($orig_max_x) || !empty($orig_max_y)) {
			$orig_result = common()->make_thumb($photo_path, $photo_path, $orig_max_x, $orig_max_y);
			if (!$orig_result || !file_exists($photo_path) || !filesize($photo_path)) {
				_re(t("Cant resize original image"));
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
		foreach ((array)$this->GALLERY_OBJ->PHOTO_TYPES as $format_name => $format_info) {
			$thumb_path = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $format_name);
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
		$GALLERY_SETTINGS = $this->GALLERY_OBJ->_get_settings($photo_info["user_id"]);

		$photo_path		= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "original");
		if (!file_exists($photo_path)) {
// TODO: maybe wee need to delete such photo?
			return false;
		}
		// Resize all image sizes
		foreach ((array)$this->GALLERY_OBJ->PHOTO_TYPES as $format_name => $format_info) {
			if ($format_name == "original") {
				continue;
			}
			if (!empty($ONLY_ONE_FORMAT) && $ONLY_ONE_FORMAT != $format_name) {
				continue;
			}
			$new_thumb_path = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $format_name);
			$new_thumb_dir	= dirname($new_thumb_path)."/";
			// Create folder if not exists
			if (!file_exists($new_thumb_dir)) {
				_mkdir_m($new_thumb_dir, $this->GALLERY_OBJ->DEF_DIR_MODE, 1);
			}
			// Try to create thumb
			$limit_x = $format_info["max_x"];
			$limit_y = $format_info["max_y"];
			// Override medium size
			if ($format_name == "medium" && $GALLERY_SETTINGS["medium_size"]) {
				$limit_x = $GALLERY_SETTINGS["medium_size"];
			}
			// Force square crop
			if ($GALLERY_SETTINGS["thumb_type"] == 1 && in_array($format_name, array("thumbnail", "ad thumbnail"))) {
				@copy($photo_path, $new_thumb_path);

				$OBJ = main()->init_class("image_manip", "classes/common/");
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
			$this->GALLERY_OBJ->_update_other_info($photo_info);
		}
	}
	
	/**
	* Prepare photo name
	*/
	function _prepare_photo_name($name = "") {
		$name = substr(trim($name), 0, 32);
		$name = common()->make_translit($name);
		$name = preg_replace("/[^0-9a-z\-\_\.]/i", "_", $name);
		return $name;
	}

	/**
	* Do crop photo
	*/
	function _crop ($photo_info, $pos_left, $pos_top, $crop_width, $crop_height) {
		$original_path 	= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "original");
		$medium_path 	= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "medium");
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
		$OBJ = main()->init_class("image_manip", "classes/common/");
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
		$original_path	 	= $this->GALLERY_OBJ->_photo_fs_path($photo_info, "original");
		// Go
		$OBJ = main()->init_class("image_manip", "classes/common/");
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
		if (empty($this->GALLERY_OBJ->_user_info) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		// Ban check
		if ($this->GALLERY_OBJ->_user_info["ban_images"]) {
			return $this->GALLERY_OBJ->_error_msg("ban_images");
		}
		// Try to get given photo info
		$sql = "SELECT * FROM ".db('gallery_photos')." WHERE ";
		if ($this->GALLERY_OBJ->HIDE_TOTAL_ID && $this->GALLERY_OBJ->USER_ID && !$force_photo_id) {
			$sql .= " id2=".intval($PHOTO_ID)." AND user_id=".intval($this->GALLERY_OBJ->USER_ID);
		} else {
			$sql .= " id=".intval($PHOTO_ID);
		}
		$photo_info = db()->query_fetch($sql);
		if (empty($photo_info["id"])) {
			return _e(t("No such photo!"));
		}
		// Check owner
		if (MAIN_TYPE_USER && $photo_info["user_id"] != $this->GALLERY_OBJ->USER_ID) {
			return _e(t("Not your photo!"));
		}

		// Do not hide broken photos from owner
		$this->GALLERY_OBJ->SKIP_NOT_FOUND_PHOTOS = false;

		return $photo_info;
	}
}
