<?php

/**
* Forum authentication using global system users
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_auth_global {

	/** @var int */
	var $LASTUP_TTL				= 900; // 15*60 = 15 minutes

	/**
	* Constructor
	*/
	function _init () {
		// Set session expiration time
		if (isset(module('forum')->SETTINGS["SESSION_EXPIRE_TIME"])) {
			$this->LASTUP_TTL = module('forum')->SETTINGS["SESSION_EXPIRE_TIME"];
		}
	}

	/**
	* Log in function
	*/
	function _login () {
		return js_redirect("./?object=login");
	}

	/**
	* Log out function
	*/
	function _logout () {
		return js_redirect("./?object=logout");
	}

	/**
	* Verify session variables
	*/
	function _verify_session_vars () {
		$user_id			= intval($_SESSION["user_id"]);
		$global_group_id	= intval($_SESSION["user_group"]);
		if (!empty($user_id)) {
			$user_info = &main()->USER_INFO;
			if (!$user_info) {
				$user_info	= user($user_id, array("name","nick"));
			}
			$user_name	= _display_name($user_info);
			// Get forum user info
			$forum_user_info = module('forum')->_get_users_infos($user_id, array("forum_only" => 1));
			$forum_user_info = $forum_user_info[$user_id];
		}
		$visibility = 0;
		// Check if user is ban list
		$IS_BANNED = $this->_check_user_ban();
		// Get forum user group
		if (!empty($forum_user_info["forum_group"])) {
			$group_id = intval($forum_user_info["forum_group"]);
		} elseif (!empty($user_info)) {
			$group_id = 3;	// Members forum group
		} else {
			$group_id = 4;	// Guests forum group
		}
		// Force banned users forum group
		if ($IS_BANNED) {
			$group_id = 5;
		}
		// Get group info
		$cur_user_group = &module('forum')->_forum_groups[$group_id];
		$group_name = $cur_user_group["title"];
		// Special constants
		define('FORUM_IS_ADMIN',		intval($group_id == 1 || $cur_user_group["is_admin"]));
		define('FORUM_IS_MODERATOR',	intval($group_id == 2 || $cur_user_group["is_moderator"]));
		define('FORUM_IS_GUEST',		intval($group_id == 4));
		define('FORUM_IS_BANNED',		intval($group_id == 5));
//		define('FORUM_IS_MEMBER',		intval($group_id == 3));
		define('FORUM_IS_MEMBER',		intval(!FORUM_IS_GUEST && !FORUM_IS_BANNED));
		// Define forum constants
		define('FORUM_USER_ID',			intval($user_id));
		define('FORUM_USER_NAME',		strlen($user_name) ? _prepare_html($user_name) : "");
		define('FORUM_USER_GROUP_ID',	intval($group_id));
		define('FORUM_USER_GROUP_NAME',	strlen($group_name) ? _prepare_html($group_name) : "");
		define('FORUM_USER_LAST_VISIT',	intval($user_info["user_lastvisit"]));
		define('FORUM_USER_VISIBLE',	intval($visibility));
		define('FORUM_USER_TIME_ZONE',	floatval($user_time_zone));
		// Merge group rights with default ones
		foreach ((array)module('forum')->USER_RIGHTS as $rights_key => $rights_value) {
			if (!isset($cur_user_group[$rights_key])) {
				continue;
			}
			module('forum')->USER_RIGHTS[$rights_key] = $cur_user_group[$rights_key];
		}
		// Try to get forum settings for the current user
		if (FORUM_USER_ID) {
			module('forum')->_cur_user_settings = $forum_user_info;
			$cur_user_settings = &module('forum')->_cur_user_settings;
		}
		// Set current user settings (for logged in users only)
		if (FORUM_USER_ID && !FORUM_INTERNAL_CALL && !empty($cur_user_settings)) {
			module('forum')->USER_SETTINGS = array(
				"VIEW_SIG"			=> $cur_user_settings["view_sig"],
				"VIEW_IMAGES"		=> $cur_user_settings["view_images"],
				"VIEW_AVATARS"		=> $cur_user_settings["view_avatars"],
				"POSTS_PER_PAGE"	=> $cur_user_settings["posts_per_page"],
				"TOPICS_PER_PAGE"	=> $cur_user_settings["topics_per_page"],
				"PREFERRED_SKIN"	=> $cur_user_settings["skin"],
			);
		}
		// Process online users
		if (module('forum')->SETTINGS["ONLINE_USERS_STATS"] && !FORUM_INTERNAL_CALL && !FORUM_IS_BANNED) {
			$this->_process_online();
		}
	}

	/**
	* Process online users info
	*/
	function _process_online () {
		$cur_time = time();
		// Cleanup expired users
		if (!main()->USE_TASK_MANAGER) {
			db()->query("DELETE FROM `".db('forum_sessions')."` WHERE `last_update` < ".(time() - $this->LASTUP_TTL));
		}
		// Try to recognize well-known spiders
		if (module('forum')->SETTINGS["RECOGNIZE_SPIDERS"]) {
			$spider_name = main()->call_class_method("spider_detect", "classes/", "detect");
			if (!empty($spider_name)) {
				return false;
			}
		}
		// Get topic and forum ID
		if ($_GET["action"] == "view_forum") {
			$in_forum = intval($_GET["id"]);
			$in_topic = 0;
		} elseif ($_GET["action"] == "view_topic") {
			$in_topic = intval($_GET["id"]);
			// Get topic info
			if (empty(module('forum')->_topic_info) && !empty($_GET["id"])) {
				module('forum')->_topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			}
			$in_forum = intval(module('forum')->_topic_info["forum"]);
		}
		// Create compact user location string
		$location = $_GET["action"].";".$_GET["id"].";".$_GET["page"];
		// Visible or not for other members except admin
		$login_type = FORUM_USER_VISIBLE;
		// Current user session ID
		$_session_id = session_id();
		// Get all users online
		$Q = db()->query("SELECT * FROM `".db('forum_sessions')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$online_array[$A["id"]] = $A;
		}
		module("forum")->online_array = $online_array;
		$online_users_array = &module("forum")->online_array;
		$cur_user_session_info = &$online_users_array[$_session_id];
		// Get user login date
		if (FORUM_USER_ID) {
			$login_date = !empty($cur_user_session_info["login_date"]) ? $cur_user_session_info["login_date"] : time();
		} else {
			$login_date = 0;
		}
		// Update current user session info
		$cur_user_session_info["login_date"]	= $login_date;
		$cur_user_session_info["last_update"]	= $cur_time;
		$cur_user_session_info["location"]		= $location;
		$cur_user_session_info["in_forum"]		= $in_forum;
		$cur_user_session_info["in_topic"]		= $in_topic;
		// Refresh current session reocrd
		db()->REPLACE("forum_sessions", array(
			"id"			=> _es($_session_id),
			"user_id"		=> intval(FORUM_USER_ID),
			"user_name"		=> _es(FORUM_USER_NAME),
			"user_group"	=> intval(FORUM_USER_GROUP_ID),
			"ip_address"	=> _es(common()->get_ip()),
			"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
			"login_date"	=> intval($login_date),
			"last_update"	=> intval($cur_time),
			"login_type"	=> intval($login_type),
			"location"		=> _es($location),
			"in_forum"		=> intval($in_forum),
			"in_topic"		=> intval($in_topic),
		));
		// Update member's record
		if (FORUM_USER_ID) {
//			db()->query("UPDATE `".db('forum_users')."` SET `user_lastvisit` = ".$cur_time." WHERE `id`=".intval(FORUM_USER_ID));
		}
	}
	
	/**
	* Check if user is in ban list
	*/
	function _check_user_ban () {
		// Check if user in ban list
		if (module('forum')->SETTINGS["USE_BAN_IP_FILTER"]) {
			if (db()->query_num_rows("SELECT `ip` FROM `".db('bannedip')."` WHERE `ip`='"._es(common()->get_ip())."'")) {
				module('forum')->BAN_REASONS[] = "Your IP address was found in ban list!";
			}
		}

//		module('forum')->BAN_REASONS[] = "Your IP address was found in ban list!";

		return !empty(module('forum')->BAN_REASONS) ? 1 : 0;
	}
}
