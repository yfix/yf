<?php

/**
* Gallery utils
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_gallery_utils {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->GALLERY_OBJ	= module(GALLERY_CLASS_NAME);
	}

	/**
	* Get real photo sizes from photo files for the given photo db record
	*/
	function _update_other_info ($photo_info = array()) {
		$other_info_array = array();
		// Go through photo types
		foreach ((array)$this->GALLERY_OBJ->PHOTO_TYPES as $type_name => $type_info) {
			$cur_file_full_path = $this->GALLERY_OBJ->_photo_fs_path($photo_info, $type_name);
			// We need to calculate it real dimensions
			list($i_width, $i_height,,) = getimagesize($cur_file_full_path);
			// Fill other info array
			$other_info_array[$type_name] = array(
				"w"	=> intval($i_width),
				"h"	=> intval($i_height),
			);
		}
		// Update user photos with gathered info
		db()->query("UPDATE `".db('gallery_photos')."` SET `other_info`='"._es(serialize($other_info_array))."' WHERE `id`=".intval($photo_info["id"]));
		// Return other info
		return $other_info_array;
	}

	/**
	* Get max privacy value that current user can view
	*/
	function _get_max_privacy ($user_id = 0) {
		$max_privacy = 1;
		// Admin can do anything
		if (MAIN_TYPE_ADMIN) {
			$max_privacy = 9;
			return $max_privacy;
		}
		// Guest
		if (!$this->GALLERY_OBJ->USER_ID || empty($user_id)) {
			return $max_privacy;
		}
		$is_member = true;
		// Check gallery owner
		if (!isset($this->GALLERY_OBJ->is_own_gallery)) {
			$this->GALLERY_OBJ->is_own_gallery = false;
			if (MAIN_TYPE_USER && $this->GALLERY_OBJ->USER_ID && $this->GALLERY_OBJ->USER_ID == $user_id) {
				$this->GALLERY_OBJ->is_own_gallery = true;
			} elseif (MAIN_TYPE_ADMIN) {
				$this->GALLERY_OBJ->is_own_gallery = true;
			}
		}
		// Owner
		if ($this->GALLERY_OBJ->is_own_gallery) {
			$max_privacy = 9;
			return $max_privacy;
		}
		// Check friendship
		$FRIENDS_OBJ = main()->init_class("friends");
		if (is_object($FRIENDS_OBJ)) {
			$is_in_his_friends	= $FRIENDS_OBJ->_is_a_friend($this->GALLERY_OBJ->USER_ID, $user_id);
			$is_my_friend		= $FRIENDS_OBJ->_is_a_friend($user_id, $this->GALLERY_OBJ->USER_ID);
			$is_mutual_friends	= $is_in_his_friends && $is_my_friend;
		}
		if ($is_member) {
			$max_privacy	= 2;
		}
		if ($is_in_his_friends) {
			$max_privacy	= 3;
		}
		if ($is_my_friend) {
			$max_privacy	= 4;
		}
		if ($is_mutual_friends) {
			$max_privacy	= 5;
		}
		return $max_privacy;
	}

	/**
	* Check privacy permissions (allow current user to view or not)
	*/
	function _privacy_check ($folder_privacy = 0, $photo_privacy = 0, $owner_id = 0) {
		// Public folder and photos
		if ($folder_privacy <= 1 && $photo_privacy <= 1) {
			return true;
		}
		// This is owner
		if (($this->GALLERY_OBJ->USER_ID && $owner_id == $this->GALLERY_OBJ->USER_ID) || MAIN_TYPE_ADMIN) {
			return true;
		}
		// Public section was over, now begin checking for members,
		// so if user is guest - we deny view here
		if (!$this->GALLERY_OBJ->USER_ID) {
			return false;
		}
		// Currently user can set more private status for the current 
		// photo comparing to folder global settings (we trying to find greatest private value)
		$cur_privacy = $photo_privacy > $folder_privacy ? $photo_privacy : $folder_privacy;
		// For members
		if ($cur_privacy == 2) {
			return true;
		// Friends (simple, user need only to add photo owner to his friends list)
		} elseif ($cur_privacy == 3) {
			if ($owner_id != $this->GALLERY_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend = $FRIENDS_OBJ->_is_a_friend($this->GALLERY_OBJ->USER_ID, $owner_id);
					return $is_a_friend;
				}
			}
			return true;
		// My friends (simple, user need to be in photo owner's friends list)
		} elseif ($cur_privacy == 4) {
			if ($owner_id != $this->GALLERY_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_my_friend = $FRIENDS_OBJ->_is_a_friend($owner_id, $this->GALLERY_OBJ->USER_ID);
					return $is_my_friend;
				}
			}
			return true;
		// Mutual Friends (both users must have each other in friends lists)
		} elseif ($cur_privacy == 5) {
			if ($owner_id != $this->GALLERY_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend_1 = $FRIENDS_OBJ->_is_a_friend($this->GALLERY_OBJ->USER_ID, $owner_id);
					$is_a_friend_2 = $FRIENDS_OBJ->_is_a_friend($owner_id, $this->GALLERY_OBJ->USER_ID);
					return $is_a_friend_1 && $is_a_friend_2;
				}
			}
			return true;
		// Diary
		} elseif ($cur_privacy == 9) {
			return true;
		}
		// In all other cases -> deny view
		return false;
	}

	/**
	* Check allow comments (allow current user to view/post or not)
	*/
	function _comment_allowed_check ($folder_comments = 0, $photo_comments = 0, $owner_id = 0) {
		// Public folder and photos
		if ($folder_comments <= 1 && $photo_comments <= 1) {
			return true;
		}
		// Public section was over, now begin checking for members,
		// so if user is guest - we deny view here
		if (!$this->GALLERY_OBJ->USER_ID) {
			return false;
		}
		// Currently user can set more private status for the current 
		// photo comparing to folder global settings (we trying to find greatest private value)
		$cur_comments = $photo_comments > $folder_comments ? $photo_comments : $folder_comments;
		// For members
		if ($cur_comments == 2) {
			return true;
		// Friends (simple, user need only to add photo owner to his friends list)
		} elseif ($cur_comments == 3) {
			if ($owner_id != $this->GALLERY_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend = $FRIENDS_OBJ->_is_a_friend($this->GALLERY_OBJ->USER_ID, $owner_id);
					return $is_a_friend;
				}
			}
			return true;
		// My friends (simple, user need to be in photo owner's friends list)
		} elseif ($cur_comments == 4) {
			if ($owner_id != $this->GALLERY_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_my_friend = $FRIENDS_OBJ->_is_a_friend($owner_id, $this->GALLERY_OBJ->USER_ID);
					return $is_my_friend;
				}
			}
			return true;
		// Mutual Friends (both users must have each other in friends lists)
		} elseif ($cur_comments == 5) {
			if ($owner_id != $this->GALLERY_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend_1 = $FRIENDS_OBJ->_is_a_friend($this->GALLERY_OBJ->USER_ID, $owner_id);
					$is_a_friend_2 = $FRIENDS_OBJ->_is_a_friend($owner_id, $this->GALLERY_OBJ->USER_ID);
					return $is_a_friend_1 && $is_a_friend_2;
				}
			}
			return true;
		// Disabled (No comments)
		} elseif ($cur_comments == 9) {
			return false;
		}
		// In all other cases -> deny view
		return false;
	}

	/**
	* Sync public photos
	*/
	function _sync_public_photos ($user_id = 0) {
		db()->query(
			"UPDATE `".db('gallery_photos')."` SET `is_public` = '0'
			".($user_id ? " WHERE `user_id` = ".intval($user_id) : "")
		);
		$sql = 
			"UPDATE `".db('gallery_photos')."` 
			SET `is_public` = '1'
			WHERE `active` = '1'
				AND `folder_id` IN(
					SELECT `id` 
					FROM `".db('gallery_folders')."` 
					WHERE `privacy`<=1 
						AND `content_level`<=1 
						AND `password`='' 
						AND `active`='1' 
						".($user_id ? " AND `user_id` = ".intval($user_id) : "")."
				)".($user_id ? " AND `user_id` = ".intval($user_id) : "");
		db()->query($sql);
		// Update gallery photos with geo location
		db()->query(
			"UPDATE `".db('gallery_photos')."` 
			SET `geo_cc` = '', `geo_rc` = ''
			".($user_id ? " WHERE `user_id` = ".intval($user_id) : "")
		);
		db()->query(
			"UPDATE `".db('gallery_photos')."` AS `p`
				, `".db('user')."` AS `u`
			SET `p`.`geo_cc` = `u`.`country`
				, `p`.`geo_rc` = `u`.`state`
			WHERE `p`.`user_id` = `u`.`id`
			".($user_id ? " AND `u`.`id` = ".intval($user_id) : "")
		);
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($OBJ = false) {
		if (!is_object($OBJ)) {
			return false;
		}

		// Main page		
		$OBJ->_store_item(array(
			"url"	=> "./?object=gallery&action=show",
		));

		// Get medium size and full size photos list from db
		$sql = "SELECT `id` FROM `".db('gallery_photos')."` WHERE `active`='1' AND `is_public`='1'";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=gallery&action=show_medium_size&id=".$A["id"],
			));
			$OBJ->_store_item(array(
				"url"	=> "./?object=gallery&action=show_full_size&id=".$A["id"],
			));
		}

		// Get folders from db
		$sql = "SELECT `id` FROM `".db('gallery_folders')."` WHERE `active`='1' AND `privacy`='0'";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=gallery&action=view_folder&id=".$A["id"],
			));
		}

		// Get galleries from db
		$sql = "SELECT DISTINCT `user_id` FROM `".db('gallery_photos')."` WHERE `active`='1' AND `is_public`='1'";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=gallery&action=show_gallery&id=".$A["user_id"],
			));
		}

		$sql = "SELECT COUNT(DISTINCT `user_id`) AS `num` FROM `".db('gallery_photos')."` WHERE `active`='1' AND `is_public`='1'";
		$A = db()->query_fetch($sql);
		$total_pages = ceil(intval($A["num"]) / intval($this->GALLERY_OBJ->VIEW_ALL_ON_PAGE));
		// Process pages
		if ($total_pages > 1) {
			for ($i = 1; $i <= $total_pages; $i++) {
				$OBJ->_store_item(array(
					"url"	=> "./?object=gallery&action=show_all_galleries&id=all&page=".$i,
				));
			}	
		} else {
			$OBJ->_store_item(array(
				"url"	=> "./?object=gallery&action=show_all_galleries&id=all",
			));
		}
		return true;
	}
}
