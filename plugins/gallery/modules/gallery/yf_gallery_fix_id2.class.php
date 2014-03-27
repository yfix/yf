<?php

/**
*/
class yf_gallery_fix_id2 {
	
	/**
	* Fix second id (used for HIDE_TOTAL_ID)
	*/
	function _fix_id2($user_id = 0) {
		if (empty($user_id) || !module('gallery')->HIDE_TOTAL_ID) {
			return false;
		}
		$_max_id2++;
		// Get all user photos
		$Q = db()->query('SELECT id,id2 FROM '.db('gallery_photos').' WHERE user_id='.intval($user_id).' ORDER BY id ASC');
		while ($A = db()->fetch_assoc($Q)) {
			$photos[$A['id']] = $A['id2'];
			if ($A['id2'] > $_max_id2) {
				$_max_id2 = $A['id2'];
			}
		}
		$photos_to_update	= array();
		$existed_second_ids = array();
		// Check duplicates or empty ids
		foreach ((array)$photos as $_photos_id => $_info) {
			if (empty($_info['id2'])) {
				$photos_to_update[$_photos_id] = $_info;
				continue;
			}
			// Duplicate ones
			if (isset($existed_second_ids[$_info['id2']])) {
				$photos_to_update[$_photo_id] = $_info;
			}
			$existed_second_ids[$_info['id2']] = $_info['id2'];
		}
		foreach ((array)$photos_to_update as $_photo_id => $_photo_info) {
			$_max_id2++;
			db()->UPDATE('gallery_photos', array('id2' => intval($_max_id2)), 'id='.intval($_photo_id));
		}
		_class_safe('gallery_folders', 'modules/gallery/')->_fix_folder_id2($user_id);
		return $_max_id2;
	}
}
