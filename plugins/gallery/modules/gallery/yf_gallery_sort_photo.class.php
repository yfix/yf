<?php

/**
*/
class yf_gallery_sort_photo {

	/**
	* Change photo sorting id
	*/
	function sort_photo () {
		// Second id passed in $_GET["id"] (example: 14_56)
		if (false !== strpos($_GET["id"], "_")) {
			list($_GET["id"], $_second_get_id) = explode("_", $_GET["id"]);
			$_second_get_id = intval($_second_get_id);
		}
		$photo_info = _class('gallery_manage', 'modules/gallery/')->_acl_manage_checks();
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
				WHERE user_id=".intval(main()->USER_ID)." 
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
					"UPDATE ".db('gallery_photos')." SET ".$FIELD_NAME." = id WHERE id IN(".implode(",", $_ids_to_update).") AND user_id=".intval(main()->USER_ID)
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
		if (!empty($SECOND_PHOTO_ID)) {
			db()->UPDATE("gallery_photos", array($FIELD_NAME => intval($_sort_ids[$SECOND_PHOTO_ID])), "id=".intval($photo_info["id"]));
			db()->UPDATE("gallery_photos", array($FIELD_NAME => intval($photo_info[$FIELD_NAME])), "id=".intval($SECOND_PHOTO_ID));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo "1";
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}
}
