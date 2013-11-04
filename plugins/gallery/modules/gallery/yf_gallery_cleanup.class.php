<?php

/**
* Gallery cleaner
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_cleanup {

	/** @var bool Useful for debugging @conf_skip */
	public $ALLOW_STEP_1 = 0;
	/** @var bool Useful for debugging @conf_skip */
	public $ALLOW_STEP_2 = 1;
	/** @var bool Useful for debugging @conf_skip */
	public $ALLOW_STEP_3 = 1;
	
	/**
	* Do cleanup
	*/
	function _cleanup() {
// TODO: this code is outdated, need revision and upgrade
		ignore_user_abort(1);
		// Sleep every cycles
		set_time_limit(600);
		$SLEEP_DURATION			= 2; // in sec
		$SLEEP_EVERY_NUM_CYCLES	= 1000;
		$DELETE_WRONG_RECORDS	= 0;
		// Delete all inactive gallery photos records
		db()->query("DELETE FROM ".db('gallery_photos')." WHERE active='0'");
		// Start to cleanup "dead" photos
		$path_to_photos = INCLUDE_PATH.module('gallery')->GALLERY_DIR;
		// ############ STEP 1 ###############
		// Cleanup non-linked to db photos files
		// ###################################
		if ($this->ALLOW_STEP_1) {
			// Get max user_id from db
			$_table_info = db()->query_fetch("SHOW TABLE STATUS LIKE '".db('user')."'");
			$MAX_USER_ID = $table_info["Auto_increment"] - 1;
			// Process members
			for ($i = 1; $i < $MAX_USER_ID; $i++) {
				// Sleep if needed
				if (!($i % $SLEEP_EVERY_NUM_CYCLES)) {
					sleep($SLEEP_DURATION);
				}
				// Assign some content
				$user_known_photos	= array();
				// Check if such user exists
				$try_user_info = user($i, array("id"));
				$USER_EXISTS = !empty($try_user_info["id"]) ? 1 : 0;
				// Get user photos records
				if ($USER_EXISTS) {
					$Q = db()->query("SELECT * FROM ".db('gallery_photos')." WHERE user_id=".intval($i));
					while ($A = db()->fetch_assoc($Q)) $user_known_photos[$A["id"]] = $A;
				}
				// Array of additional photos infos
				$user_photos_other_infos = array();
				// Prepare path to the user's folder
				$cur_user_folder = _gen_dir_path($i);
				// Process gallery photo types
				foreach ((array)module('gallery')->PHOTO_TYPES as $type_name => $type_info) {
					$cur_type_dir	= $path_to_photos. $type_info["sub_folder"]. $cur_user_folder;
					// Skip non-existed dirs
					if (!file_exists($cur_type_dir)) {
						continue;
					}
					// Do delete subfolders if user not exists
					if (!$USER_EXISTS) {
						_class("dir")->delete_dir($cur_type_dir, 1);
						continue;
					}
					// Prepare photos names
					$avail_photos_names	= array();
					foreach ((array)$user_known_photos as $_photo_info) {
						$avail_photos_names[$_photo_info["id"]] = module('gallery')->_create_name_from_tpl($_photo_info, $type_name, 0).".jpg";
					}
					// Do delete subfolders if user have no photos
					if (empty($avail_photos_names)) {
						_class("dir")->delete_dir($cur_type_dir, 1);
						continue;
					}
					if (!$dh = @opendir($cur_type_dir)) {
						continue;
					}
					// Get all image files inside current dir
					while (($f = @readdir($dh)) !== false) {
						if ($f == "." || $f == ".." || is_dir($f)) {
							continue;
						}
						$cur_ext = common()->get_file_ext($f);
						// Skip non-images
						if ($cur_ext != "jpg") {
							continue;
						}
						$cur_file_full_path = $cur_type_dir."/".$f;
						// Now trying to find such photo inside user photos
						if (!in_array($f, $avail_photos_names)) {
							unlink($cur_file_full_path);
						}
						// Finally we passed all checks and can work with correct file
						// We need to calculate it real dimensions
						$image_info = @getimagesize($cur_file_full_path);
						if (is_array($image_info)) {
							list($i_width, $i_height,,) = $image_info;
						}
						// Get photo_id from name
						$_try_photo_info = module('gallery')->_get_info_from_file_name($f);
						// Fill other info array
						$user_photos_other_infos[$_try_photo_info["photo_id"]][$type_name] = array(
							"w"	=> intval($i_width),
							"h"	=> intval($i_height),
						);
					}
					@closedir($dh);
				}
				// Check if user has no known photos
				if (empty($user_known_photos)) {
					continue;
				}
				// Update user photos with gathered info
				foreach ((array)$user_photos_other_infos as $_photo_id => $_cur_photo_info) {
					db()->query("UPDATE ".db('gallery_photos')." SET other_info='"._es(serialize($_cur_photo_info))."' WHERE id=".intval($_photo_id));
				}
			}
		}
		// ############ STEP 2 ###############
		// Cleanup db records with missing photo files
		// Also we check for non-transformed photo sizes
		// ###################################
		if ($this->ALLOW_STEP_2) {
			$have_netpbm = (!defined("NETPBM_PATH") || NETPBM_PATH == "");
			$counter = 0;
			$Q = db()->query("SELECT * FROM ".db('gallery_photos')."");
			while ($A = db()->fetch_assoc($Q)) {
				// Sleep if needed
				if (!($counter++ % $SLEEP_EVERY_NUM_CYCLES)) {
					sleep($SLEEP_DURATION);
				}
				$PHOTO_CORRUPTED	= false;
				$PHOTO_HAS_PROBLEMS	= false;
				// Prepare path to the user's folder
				$cur_user_folder = _gen_dir_path($A["user_id"]);
				// First we need to check original photo
				$original_photo_path = $path_to_photos. module('gallery')->PHOTO_TYPES["original"]["sub_folder"]. $cur_user_folder .module('gallery')->_create_name_from_tpl($A, "original", 0).".jpg";
				if (!file_exists($original_photo_path) || !is_readable($original_photo_path) || !filesize($original_photo_path)) {
					$PHOTO_CORRUPTED = true;
				}
				// Check mime type
				if (!$PHOTO_CORRUPTED) {
					$original_image_info = @getimagesize($original_photo_path);
					if (empty($original_image_info) || !in_array($original_image_info["mime"], array("image/pjpeg", "image/jpeg"))) {
						$PHOTO_CORRUPTED = true;
						if ($DELETE_WRONG_RECORDS) {
							unlink($original_photo_path);
						}
					}
				}
				// Check for images that crashes GD (and we have no NETPBM)
				if (!$PHOTO_CORRUPTED && $have_netpbm) {
					if (false === @imagecreatefromjpeg($original_photo_path)) {
						$PHOTO_CORRUPTED = true;
						if ($DELETE_WRONG_RECORDS) {
							unlink($original_photo_path);
						}
					}
				}
				// Process gallery photo types
				if (!$PHOTO_CORRUPTED) {
					foreach ((array)module('gallery')->PHOTO_TYPES as $type_name => $type_info) {
						// We did special processing of the original photos before
						if ($type_name == "original") {
							continue;
						}
						$NEED_RESIZE = false;
						$cur_type_dir	= $path_to_photos. $type_info["sub_folder"]. $cur_user_folder;
						$cur_photo_path = $cur_type_dir.module('gallery')->_create_name_from_tpl($A, $type_name, 0).".jpg";
						// Check if file is ok
						if (file_exists($cur_photo_path) && filesize($cur_photo_path) && is_readable($cur_photo_path)) {
							continue;
						}
						// Check mime type
						$cur_image_info = @getimagesize($cur_photo_path);
						if (empty($cur_image_info) || !in_array($cur_image_info["mime"], array("image/pjpeg", "image/jpeg")) || empty($cur_image_info[0]) || empty($cur_image_info[1])) {
							unlink($cur_photo_path);
							$NEED_RESIZE = true;
						}
						// Resize if needed
						if ($NEED_RESIZE) {
							$resize_result = common()->make_thumb($original_photo_path, $cur_photo_path, $type_info["max_x"], $type_info["max_y"]);
							// Check if photo resized successfully
							if (!file_exists($cur_photo_path)) {
								$PHOTO_HAS_PROBLEMS = true;
							} else {
								$cur_image_info = @getimagesize($cur_photo_path);
								if (empty($cur_image_info) || empty($cur_image_info[0]) || empty($cur_image_info[1])) {
									$PHOTO_HAS_PROBLEMS = true;
								}
							}
							// Increment counter
							$counter++;
						}
					}
				}
				// Deactivate record if photo is wrong
				if ($PHOTO_CORRUPTED || $PHOTO_HAS_PROBLEMS) {
					if ($DELETE_WRONG_RECORDS && !$PHOTO_HAS_PROBLEMS) {
						db()->query("DELETE FROM ".db('gallery_photos')." WHERE id=".intval($A["id"]));
					} else {
						db()->query("UPDATE ".db('gallery_photos')." SET active='0' WHERE id=".intval($A["id"]));
					}
				} else {
					db()->query("UPDATE ".db('gallery_photos')." SET active='1' WHERE id=".intval($A["id"]));
				}
			}
		}
		// ############ STEP 3 ###############
		// Check folders
		// ###################################
		if ($this->ALLOW_STEP_3) {
			$counter = 0;
			// Prepare data
			$def_title		= module('gallery')->DEFAULT_FOLDER_NAME;
			$is_default		= 1;
			$creation_date	= time();
			// Delete photos with missing users
			db()->query(
				"DELETE FROM ".db('gallery_photos')." 
					WHERE user_id NOT IN(
						SELECT id FROM ".db('user')."
					)"
			);
			// Delete folders with missing users
			db()->query(
				"DELETE FROM ".db('gallery_folders')." 
					WHERE user_id NOT IN(
						SELECT id FROM ".db('user')."
					)"
			);
			// Create temporary table
			$tmp_table_name = db()->_get_unique_tmp_table_name();
			db()->query(
				"CREATE TEMPORARY TABLE ".$tmp_table_name." ( 
					user_id		int(10) unsigned NOT NULL, 
					def_folder_id	int(10) unsigned NOT NULL, 
					PRIMARY KEY (user_id),
					KEY (def_folder_id)
				)"
			);
			// Save all users with folders
			db()->query(
				"REPLACE INTO ".$tmp_table_name." (user_id) 
					SELECT DISTINCT(user_id) 
					FROM ".db('gallery_folders')." 
					WHERE is_default='1'"
			);
			// First get all users without folders and create default folders
			$sql = "INSERT INTO ".db('gallery_folders')." ( 
					user_id,
					title,
					is_default,
					add_date
				) SELECT DISTINCT(p.user_id), 
					'"._es($def_title)."',
					'".$is_default."', 
					'".intval($creation_date)."'
				FROM ".db('gallery_photos')." AS p
				WHERE p.user_id NOT IN ( 
						SELECT user_id FROM ".$tmp_table_name."
					)";
			db()->query($sql);
			// Cleanup temp
			db()->query("TRUNCATE TABLE ".$tmp_table_name."");
			// Update (default folders ids <=> users ids)
			db()->query(
				"REPLACE INTO ".$tmp_table_name." (user_id,def_folder_id) 
					SELECT DISTINCT(user_id), id
					FROM ".db('gallery_folders')." 
					WHERE is_default='1'"
			);
			// Then update default folders numbers for all photos where folder_id == 0
			$sql = "UPDATE ".db('gallery_photos')." AS p
					SET p.folder_id = (
						SELECT f.def_folder_id 
						FROM ".$tmp_table_name." AS f 
						WHERE f.user_id = p.user_id
					)
					WHERE p.folder_id = 0";
			db()->query($sql);
			// Cleanup temp
			db()->query("DROP TEMPORARY TABLE ".$tmp_table_name."");
			// Optimize tables
			db()->query("OPTIMIZE TABLE ".db('gallery_folders')."");
		}
		// Update public photos
		module('gallery')->_sync_public_photos();
	}
}
