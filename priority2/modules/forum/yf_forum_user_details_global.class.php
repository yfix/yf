<?php

/**
* Display user details handler (for global users mode)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_user_details_global {

	/**
	* Constructor
	*/
	function _init () {
	}
	
	/**
	* Show user info in post
	*/
	function _show_user_details($user_info = array(), $is_online = 0, $post_user_name = "", $post_id = 0) {
		// Process user avatar
		$avatar_path	= _gen_dir_path($user_info["id"], INCLUDE_PATH. SITE_AVATARS_DIR). intval($user_info["id"]). ".jpg";
		$avatar_src		= file_exists($avatar_path) ? str_replace(INCLUDE_PATH, WEB_PATH, $avatar_path) : "";
		// Get user group
//		$user_group = t(module('forum')->FORUM_USER_GROUPS[$user_info["forum_group"]]);
		$user_group = "member";
		// Get number of user's posts
		$user_num_posts = intval($user_info["user_posts"]);
		// Process template
		$replace = array(
			"avatar_src"		=> $avatar_src,
			"user_profile_link"	=> _profile_link($user_info["id"]),
			"reput_text"		=> $post_id ? module('forum')->_reput_texts_for_posts[$post_id] : module('forum')->_reput_texts[$user_info["id"]],
			"user_name"			=> strlen($post_user_name) ? _prepare_html($post_user_name) : t("Anonymous"),
//			"user_location"		=> $user_info["user_from"],
			"user_num_posts"	=> $user_info["id"] ? $user_num_posts : "",
			"user_group"		=> $user_info["id"] ? $user_group : "",
//			"user_rank_name"	=> $user_info["id"] ? $user_rank_name : "",
//			"user_reg_date"		=> $user_info["id"] ? module('forum')->_show_date($user_info["user_regdate"], "user_reg_date") : "",
//			"user_is_online"	=> $user_info["id"] ? $is_online : "",
//			"user_is_offline"	=> $user_info["id"] ? !$is_online : "",
			"forum_profile_link"=> $user_info["id"] ? process_url(module('forum')->_user_profile_link($user_info["id"], 1)) : "",
			"use_ajax"			=> 1,
			"photo_verified"	=> $avatar_src && $user_info["photo_verified"] ? 1 : 0,
		);
		return tpl()->parse(FORUM_CLASS_NAME."/user_details_global", $replace);
	}
	
	/**
	* Get users infos
	*/
	function _get_users_infos($users_ids = array(), $params = array()) {
		if (empty($users_ids)) {
			return false;
		}
		if (is_numeric($users_ids)) {
			$users_ids = array($users_ids);
		}
		$users_array = array();
		// Process users
//		if (!isset($params["forum_only"])) {
			foreach ((array)user($users_ids, "full", array("WHERE" => array("active" => 1))) as $user_info) {
				$user_info["name"] = _display_name($A);
				$users_array[$user_info["id"]]	= $user_info;
			} 
//		}
		// Get user's forum specific info
		$Q = db()->query("SELECT * FROM `".db('forum_users')."` WHERE `id` IN(".implode(",", $users_ids).")");
		while ($user_info = db()->fetch_assoc($Q)) {
			// Merge forum settings with current ones (only non-existed ones)
			foreach ((array)$user_info as $k => $v) {
				if (!isset($users_array[$user_info["id"]][$k])) {
					$users_array[$user_info["id"]][$k] = $v;
				}
			}
			$users_array[$user_info["id"]]["forum_group"] = $user_info["group"];
			// For preventing start account twice
			$GLOBALS['forum_existed_users'][$user_info["id"]] = $user_info["id"];
		}
		if (!isset($this->_auto_connected)) {
			$this->_auto_connected = (array)main()->get_data("auto_connected");
		}
		// Start all required forum accounts
		foreach (array_keys((array)$users_array) as $_user_id) {
			if (isset($GLOBALS['forum_existed_users'][$_user_id])) {
				continue;
			}
			$_forum_user_info = $this->_start_forum_account($_user_id, $users_array[$_user_id]);
			// Merge forum settings with current ones (only non-existed ones)
			foreach ((array)$_forum_user_info as $k => $v) {
				if (!isset($users_array[$_user_id][$k])) {
					$users_array[$_user_id][$k] = $v;
				}
			}
			$users_array[$_user_id]["forum_group"] = $_forum_user_info["group"];
		}
		return $users_array;
	}
	
	/**
	* Create forum profile record automatically if needed
	*/
	function _start_forum_account ($user_id = 0, $user_info = array()) {
		if (empty($user_id)) {
			return false;
		}
		$ACCOUNT_EXISTS = isset($GLOBALS['forum_existed_users'][$user_id]);
		if ($ACCOUNT_EXISTS) {
			return false;
		}
		// Default forum user group (member)
		$forum_group = 3;
		// Check if we have user in moderators list
		foreach ((array)module('forum')->_forum_moderators as $_mod_info) {
			if ($user_id == $_mod_info["member_id"]) {
				$forum_group = 2;
				break;
			}
		}
		// Assign auto-connected user as admin
		if (isset($this->_auto_connected[$user_id])) {
			$forum_group = 1;
		}
		// Process SQL
		$forum_user_info = array(
			"id" 			=> intval($user_id),
			"status" 		=> "a",
			"name"	 		=> $user_info["name"],
			"group"	 		=> $forum_group,
			"user_regdate"	=> time(),
		);
		db()->INSERT("forum_users", $forum_user_info);

		return $forum_user_info;
	}
}
