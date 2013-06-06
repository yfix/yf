<?php

/**
* Display user details handler
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_user_details {

	/**
	* Constructor
	*/
	function _init () {
		// Get all ranks into array
		if (module('forum')->SETTINGS["SHOW_USER_RANKS"]) {
			$this->_ranks_array = main()->get_data("forum_user_ranks");
		}
		// Process user ranks
		$rank_num = 0;
		$this->_ranks_array2 = array();
		foreach ((array)$this->_ranks_array as $rank_info) {
			if ($rank_info["special"] == 1) continue;
			$this->_ranks_array2[++$rank_num] = $rank_info;
		}
	}
	
	/**
	* Show user info in post
	*/
	function _show_user_details($user_info = array(), $is_online = 0, $post_user_name = "") {
		if ($user_info["id"]) {
			// Get number of user's posts
			$user_num_posts = intval($user_info["user_posts"]);
			// Get user avatar
			if (FORUM_USER_ID && !module('forum')->USER_SETTINGS["VIEW_AVATARS"]) {
				$user_avatar_src = "";
			} elseif (!empty($user_info["user_avatar"])) {
				$img_src = module('forum')->SETTINGS["AVATARS_DIR"]. $user_info["user_avatar"];
				$user_avatar_src = file_exists(REAL_PATH. $img_src) ? WEB_PATH. $img_src : "";
			} else {
				$user_avatar_src = "";
			}
			// Get user group
			$user_group = t(module('forum')->FORUM_USER_GROUPS[$user_info["group"]]);
			// Get user rank
			foreach ((array)$this->_ranks_array2 as $rank_num => $rank_info) {
				if ($user_num_posts > $rank_info["min"]) {
					$user_rank_name = $rank_info["title"];
					$user_level		= $rank_num;
				}
			}
			// Special rank for the admins
			if ($user_info["group"] != 3) $user_rank_name = $user_group;
		} else {
			$user_rank_name = $user_group;
		}
		$replace = array(
			"user_id"			=> intval($user_info["id"]),
			"user_name"			=> strlen($post_user_name) ? _prepare_html($post_user_name) : t("Anonymous"),
			"user_location"		=> $user_info["user_from"],
			"user_profile_link"	=> $user_info["id"] ? process_url(module('forum')->_user_profile_link($user_info["id"])) : "",
			"user_num_posts"	=> $user_info["id"] ? $user_num_posts : "",
			"user_group"		=> $user_info["id"] ? $user_group : "",
			"user_avatar_src"	=> $user_info["id"] ? $user_avatar_src : "",
			"user_rank_name"	=> $user_info["id"] ? $user_rank_name : "",
			"user_reg_date"		=> $user_info["id"] ? module('forum')->_show_date($user_info["user_regdate"], "user_reg_date") : "",
			"user_is_online"	=> $user_info["id"] ? $is_online : "",
			"user_is_offline"	=> $user_info["id"] ? !$is_online : "",
			"user_level"		=> $user_info["id"] && $user_level && module('forum')->SETTINGS["SHOW_USER_LEVEL"] ? ($user_level > 1 ? range(1, $user_level) : array(1)) : "",
			"show_user_level"	=> intval($user_info["id"] && $user_level && module('forum')->SETTINGS["SHOW_USER_LEVEL"]),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/view_user_details", $replace);
	}
	
	/**
	* Get users infos
	*/
	function _get_users_infos($users_ids = array()) {
		$users_array = array();
		// Process users
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM `".db('forum_users')."` WHERE `id` IN(".implode(",", $users_ids).")");
			while ($user_info = db()->fetch_assoc($Q)) $users_array[$user_info["id"]]	= $user_info;
		}
		return $users_array;
	}
}
