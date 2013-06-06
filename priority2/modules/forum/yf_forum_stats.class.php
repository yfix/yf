<?php

/**
* Board statistics methods here
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_stats {

	/** @var int */
	var $LAST_POSTS_TIME = 900; // 15 minutes
	/** @var string */
	var $MOST_USERS_CACHE_KEY = "forum_most_users";

	/**
	* Show total board statistics
	*/
	function _show_total_board_stats() {
		// Get forum totals
		$forum_totals = main()->get_data("forum_totals");
		// Get online stats
		list($online_stats, $online_users) = $this->_get_online_stats();
		// Get active users in the past 15 minutes 
		if (module('forum')->SETTINGS["ONLINE_USERS_STATS"]) {
			$num_active_users = db()->query_num_rows("SELECT COUNT(`user_id`) AS `0` FROM `".db('forum_posts')."` WHERE `created` >= ".intval(time() - $this->LAST_POSTS_TIME)." AND `status`='a' GROUP BY `user_id`");
		}
		// Get forum most number of users
		$forum_most_users = $this->_get_most_users_array();
		// Process template
		$replace = array(
			"is_admin"				=> intval(FORUM_IS_ADMIN),
			"is_logged_in"			=> intval((bool) FORUM_USER_ID),
			"show_online_stats"		=> intval(module('forum')->SETTINGS["ONLINE_USERS_STATS"]),
			"num_guests"			=> intval($online_stats["num_guests"]),
			"num_members"			=> intval($online_stats["num_members"]),
			"num_inv_members"		=> intval($online_stats["num_inv_members"]),
			"num_total_online"		=> intval($online_stats["num_total_online"]),
			"num_active_users"		=> intval($num_active_users),
			"last_posts_time"		=> round($this->LAST_POSTS_TIME / 60, 0),
			"online_users"			=> $online_users,
			"total_posts"			=> intval($forum_totals["total_posts"]),
			"total_users"			=> intval($forum_totals["total_users"]),
			"newest_user_name"		=> $forum_totals["last_user_login"],
			"newest_user_link"		=> module('forum')->_user_profile_link($forum_totals["last_user_id"]),
			"num_most_users"		=> intval($forum_most_users["num_most_users"]),
			"most_date"				=> module('forum')->_show_date($forum_most_users["most_date"], "most_date"),
			"by_last_click_link"	=> "./?object=".FORUM_CLASS_NAME."&action=view_stats&id=1". _add_get(),
			"by_member_name_link"	=> "./?object=".FORUM_CLASS_NAME."&action=view_stats&id=2". _add_get(),
			"today_topics_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_stats&id=3". _add_get(),
			"moderators_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_stats&id=4". _add_get(),
			"today_top_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_stats&id=5". _add_get(),
			"overall_top_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_stats&id=6". _add_get(),
			"del_cookies_link"		=> "./?object=".FORUM_CLASS_NAME."&action=del_cookies". _add_get(),
			"mark_all_read_link"	=> "./?object=".FORUM_CLASS_NAME."&action=mark_read". _add_get(),
			"sync_board_link"		=> FORUM_IS_ADMIN ? "./?object=".FORUM_CLASS_NAME."&action=sync_board". _add_get() : "",
		);
		return tpl()->parse(FORUM_CLASS_NAME."/stats_board_totals", $replace);
	}

	/**
	* Show statistics for the forum contents
	*/
	function _show_forum_stats() {
		if (!module('forum')->SETTINGS["ONLINE_USERS_STATS"]) {
			return false;
		}
		// Get online stats
		list($online_stats, $online_users) = $this->_get_online_stats($_GET["id"], 0);
		// Process template
		$replace = array(
			"is_admin"				=> intval(FORUM_IS_ADMIN),
			"num_guests"			=> intval($online_stats["num_guests"]),
			"num_members"			=> intval($online_stats["num_members"]),
			"num_inv_members"		=> intval($online_stats["num_inv_members"]),
			"num_total_online"		=> intval($online_stats["num_total_online"]),
			"online_users"			=> $online_users,
		);
		return tpl()->parse(FORUM_CLASS_NAME."/stats_forum", $replace);
	}

	/**
	* Show statistics for the topic contents
	*/
	function _show_topic_stats() {
		if (!module('forum')->SETTINGS["ONLINE_USERS_STATS"]) {
			return false;
		}
		// Get online stats
		list($online_stats, $online_users) = $this->_get_online_stats(0, $_GET["id"]);
		// Process template
		$replace = array(
			"is_admin"				=> intval(FORUM_IS_ADMIN),
			"num_guests"			=> intval($online_stats["num_guests"]),
			"num_members"			=> intval($online_stats["num_members"]),
			"num_inv_members"		=> intval($online_stats["num_inv_members"]),
			"num_total_online"		=> intval($online_stats["num_total_online"]),
			"online_users"			=> $online_users,
		);
		return tpl()->parse(FORUM_CLASS_NAME."/stats_topic", $replace);
	}

	/**
	* Get online stats
	*/
	function _get_online_stats($forum_id = 0, $topic_id = 0) {
		// Get online stats
		$online_stats = array();
		$online_users_array = array();
		foreach ((array)module("forum")->online_array as $online_info) {
			// Filter users not viewing current forum or topic
			if (!empty($forum_id) && $online_info["in_forum"] != $forum_id) {
				continue;
			}
			if (!empty($topic_id) && $online_info["in_topic"] != $topic_id) {
				continue;
			}
			// Count invisible members
			if (!empty($online_info["user_id"]) && $online_info["login_type"] == 1) {
				if (FORUM_IS_ADMIN) $online_stats["num_inv_members"]++;
			// Count common members
			} elseif (!empty($online_info["user_id"])) {
				$online_stats["num_members"]++;
				// Add template record
				$replace = array(
					"user_group"		=> $online_info["user_group"],
					"user_name"			=> _prepare_html($online_info["user_name"]),
					"user_profile_link"	=> module('forum')->_user_profile_link($online_info["user_id"]),
					"user_login_date"	=> module('forum')->_show_date($online_info["login_date"], "user_login_date"),
				);
				$online_users_array[$online_info["user_name"]] = tpl()->parse(FORUM_CLASS_NAME."/stats_user_item", $replace);
			// Count guests
			} else {
				$online_stats["num_guests"]++;
			}
		}
		$online_stats["num_total_online"] = array_sum($online_stats);
		// Sort members online and add divider between records
		if (!empty($online_users_array)) {
			ksort($online_users_array);
			$online_users = implode(", ", $online_users_array);
		}
		return array($online_stats, $online_users);
	}

	/**
	* Get most visited users info
	*/
	function _get_most_users_array() {
		$NEED_UPDATE_FILE	= false;
		$NEED_UPDATE_DB		= false;
		$online_users_num = count(module("forum")->online_array);
		// Get forum most number of users
		if (main()->USE_SYSTEM_CACHE) {
			$cache = cache()->get("forum_most_users");
		}
		if (!empty($cache)) {
			$forum_most_users = $cache;
		} else {
			// Some trick here:
			// We need to store max number of users in db cache because 
			// file cache will be deleted before we can remember it
			list($most_array_from_db) = db()->query_fetch("SELECT `value` AS `0` FROM `".db('cache')."` WHERE `key`='".$this->MOST_USERS_CACHE_KEY."'");
			if (!empty($most_array_from_db)) {
				$forum_most_users = unserialize($most_array_from_db);
			} else {
				$NEED_UPDATE_DB = true;
			}
		}
		// Check if most users now is greater than in cache
		$num_most_users	= $forum_most_users["num_most_users"];
		$most_date		= $forum_most_users["most_date"];
		if ($online_users_num > $num_most_users) {
			$num_most_users		= $online_users_num;
			$most_date			= time();
			$NEED_UPDATE_FILE	= true;
		}
		$forum_most_users = array(
			"num_most_users"=> $num_most_users,
			"most_date"		=> $most_date,
		);
		// Do update file cache
		if (main()->USE_SYSTEM_CACHE) {
			if (empty($cache) || $NEED_UPDATE_FILE) {
				cache()->put("forum_most_users", $forum_most_users);
			}
		}
		// Do update db cache
		if ($NEED_UPDATE_DB || $NEED_UPDATE_FILE) {
			db()->REPLACE("cache", array(
				"key"	=> _es($this->MOST_USERS_CACHE_KEY),
				"value"	=> _es(serialize($forum_most_users)),
			));
		}
		return $forum_most_users;
	}
}
