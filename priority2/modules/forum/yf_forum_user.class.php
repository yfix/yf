<?php

/**
* User control panel - related methods
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_user {

	/**
	* Constructor
	*/
	function _init () {
		// Init bb codes module
		$this->BB_OBJ = main()->init_class("bb_codes", "classes/");
		// Required arrays
		$this->_view_sig		= array(1 => t("Yes"), 0 => t("No"));
		$this->_view_images		= array(1 => t("Yes"), 0 => t("No"));
		$this->_view_avatars	= array(1 => t("Yes"), 0 => t("No"));
		$this->_posts_per_page	= array(0 => t("Use Forum Default"));
		for ($i = 5; $i <= 40; $i += 5) $this->_posts_per_page[$i] = $i;
		$this->_topics_per_page	= array(0 => t("Use Forum Default"));
		for ($i = 5; $i <= 40; $i += 5) $this->_topics_per_page[$i] = $i;
		// Boxes array
		$this->_boxes = array(
			"user_birth"		=> 'date_box($selected, "1900-".(date("Y")-4), "_birth", "", "dmy", 1)',
			"view_sig"			=> 'select_box("view_sig",			$this->_view_sig,		$selected, 0, 2, "", false)',
			"view_images"		=> 'select_box("view_images",		$this->_view_images,	$selected, 0, 2, "", false)',
			"view_avatars"		=> 'select_box("view_avatars",		$this->_view_avatars,	$selected, 0, 2, "", false)',
			"posts_per_page"	=> 'select_box("posts_per_page",	$this->_posts_per_page,	$selected, 0, 2, "", false)',
			"topics_per_page"	=> 'select_box("topics_per_page",	$this->_topics_per_page,$selected, 0, 2, "", false)',
		);
	}

	/**
	* View user's profile
	*/
	function _view_profile () {
		$_GET["id"] = intval($_GET["id"]);
		if (/*module('forum')->SETTINGS["USE_GLOBAL_USERS"] || */(!module('forum')->USER_RIGHTS["view_member_info"] && FORUM_USER_ID != $_GET["id"])) {
// TODO: need to turn on in globals mode
//			return module('forum')->_show_error("Disabled by the site admin!");
		}
		if (!empty($_GET["id"])) {
			$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".$_GET["id"]);
		}
		if (!empty($user_info["id"]) && !module('forum')->SETTINGS["HIDE_USERS_INFO"]) {
			// Get forum totals
			$board_totals = main()->get_data("forum_totals");
			// Some user stats
			if (!empty($user_info["user_posts"])) {
				// Get forum where user is most active
				list($most_forum_id, $most_forum_posts) = db()->query_fetch("SELECT `forum` AS `0`, COUNT(`id`) AS `1` FROM `".db('forum_posts')."` WHERE `user_id`=".intval($user_info["id"])." AND `status`='a' GROUP BY `forum` ORDER BY `1` DESC LIMIT 1");
				$posts_per_day		= round($user_info["user_posts"] / (time() - $user_info["user_regdate"]) * 3600 * 24, 2);
				$posts_percent		= $board_totals["total_posts"] ? round($user_info["user_posts"] / $board_totals["total_posts"] * 100, 2) : 0;
				$most_forum_name	= module('forum')->_forums_array[$most_forum_id]["name"];
				$most_forum_percent	= $most_forum_id ? round($most_forum_posts / module('forum')->_forums_array[$most_forum_id]["num_posts"] * 100, 2) : 0;
			}
			// Determine if user is online
			$user_is_online = false;
			foreach ((array)module("forum")->online_array as $online_info) {
				if (!empty($online_info["user_id"]) && $online_info["user_id"] == $user_info["id"]) {
					$user_is_online = true;
					break;
				}
			}
			$is_my_info = intval(FORUM_USER_ID && $user_info["id"] == FORUM_USER_ID);
			// Process template
			$replace = array(
				"is_admin"				=> intval(FORUM_IS_ADMIN),
				"is_member"				=> intval(FORUM_USER_ID),
				"is_my_info"			=> $is_my_info,
				"user_name"				=> _prepare_html($user_info["name"]),
				"user_details"			=> module('forum')->_show_user_details($user_info, $user_is_online, $user_info["name"]),
				"find_user_posts_link"	=> FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=search&result_type=posts&user_id=".$user_info["id"]."&q=results"._add_get(array("page")) : "",
				"find_user_topics_link"	=> FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=search&result_type=topics&user_id=".$user_info["id"]."&q=results"._add_get(array("page")) : "",
// FIXME
				"send_pm_link"			=> /*FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=send_pm&id=".$user_info["id"]._add_get(array("page")) : */"",
				"send_email_link"		=> FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=email_user&id=".$user_info["id"]._add_get(array("page")) : "",
				"edit_profile_link"		=> $is_my_info ? "./?object=".FORUM_CLASS_NAME."&action=edit_profile"._add_get(array("page")) : "",
				"edit_user_link"		=> FORUM_IS_ADMIN ? "./?object=".FORUM_CLASS_NAME."&action=edit_profile&id=".$user_info["id"]._add_get(array("page")) : "",
				"delete_user_link"		=> FORUM_IS_ADMIN && $user_info["group"] != 1 ? "./?object=".FORUM_CLASS_NAME."&action=delete_profile&id=".$user_info["id"]._add_get(array("page")) : "",
				"user_total_posts"		=> intval($user_info["user_posts"]),
				"posts_per_day"			=> floatval($posts_per_day),
				"posts_percent"			=> floatval($posts_percent),
				"most_forum_link"		=> $most_forum_id ? module('forum')->_link_to_forum($most_forum_id) : "",
				"most_forum_name"		=> $most_forum_name,
				"most_forum_posts"		=> intval($most_forum_posts),
				"most_forum_percent"	=> floatval($most_forum_percent),
				"last_active_date"		=> !empty($user_info["user_lastvisit"]) ? module('forum')->_show_date($user_info["user_lastvisit"], "user_last_visit") : "",
				"user_is_online"		=> intval($user_is_online),
				"user_is_offline"		=> intval(!$user_is_online),
				"user_icq"				=> $user_info["user_icq"],
				"user_aim"				=> !empty($user_info["user_aim"]) ? module('forum')->_display_email($user_info["user_aim"], 0) : "",
				"user_yim"				=> !empty($user_info["user_yim"]) ? module('forum')->_display_email($user_info["user_yim"], 0) : "",
				"user_msn"				=> !empty($user_info["user_msnm"]) ? module('forum')->_display_email($user_info["user_msnm"], 0) : "",
				"user_home_page"		=> _prepare_html($user_info["user_website"]),
				"user_location"			=> $user_info["user_from"],
				"user_interests"		=> $user_info["user_interests"],
				"user_birth"			=> $user_info["user_birth"] != "0000-00-00" ? $user_info["user_birth"] : "",
				"user_add_info"			=> $user_info["add_info"],
				"user_local_time"		=> module('forum')->_show_date(time() - (module('forum')->SETTINGS["GLOBAL_TIME_OFFSET"] * 3600) - (FORUM_USER_TIME_ZONE * 3600) + $user_info["user_timezone"] * 3600),
			);
			$body .= tpl()->parse(FORUM_CLASS_NAME."/view_profile", $replace);
		} else {
			return module('forum')->_show_error(t("no_such_user"));
		}
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* User control panel home method
	*/
	function _user_cp() {
		if (!FORUM_USER_ID) {
			return _error_need_login();
		}
		// Get user info
		$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval(FORUM_USER_ID));
		// Try to create user's account (if we are in "global mode")
		if (empty($user_info["id"])) {
			$user_info = $this->_auto_create_user_profile();
		}
		// Last check user existance
		if (empty($user_info["id"])) {
			return module('forum')->_show_error("No such user!");
		}
		$reg_date = $user_info["user_regdate"];
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			$global_user_info = user(FORUM_USER_ID);
			if (!empty($global_user_info)) {
				$reg_date = $global_user_info["add_date"];
			}
		}
		// Process template
		$replace = array(
			"user_email"	=> $user_info["user_email"],
			"user_posts"	=> $user_info["user_posts"],
			"user_regdate"	=> module('forum')->_show_date($reg_date, "user_reg_date"),
			"posts_per_day"	=> round($user_info["user_posts"] / (time() - $user_info["user_regdate"]) * 3600 * 24, 2),
			"recent_topics"	=> $this->_show_recent_read_topics(),
		);
		$content = tpl()->parse(FORUM_CLASS_NAME."/user_cp/home", $replace);
		$body = $this->_user_cp_main_tpl($content);
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Edit user profile
	*/
	function _edit_profile() {
		if (!FORUM_USER_ID) {
			return module('forum')->_show_error(t("you_are_not_logged_in"));
		}
		// Admin can edit any user account
		$_GET["id"] = intval($_GET["id"]);
		$user_id = FORUM_IS_ADMIN && !empty($_GET["id"]) ? $_GET["id"] : FORUM_USER_ID;
		// Get user info from db
		if (!empty($user_id)) {
			$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval($user_id));
		}
		// Try to create user's account (if we are in "global mode")
		if (empty($user_info["id"])) {
			$user_info = $this->_auto_create_user_profile();
		}
		// Last check user existance
		if (empty($user_info["id"])) {
			return module('forum')->_show_error("No such user!");
		}
		// Check access rights
		$is_own_profile = FORUM_USER_ID && (!$_GET["id"] || $_GET["id"] == FORUM_USER_ID);
		if ($is_own_profile && !module('forum')->USER_RIGHTS["edit_own_profile"]) {
			return module('forum')->_show_error("You are not allowed to edit own profile");
		}
		if (!FORUM_IS_ADMIN && !$is_own_profile && !module('forum')->USER_RIGHTS["edit_other_profile"]) {
			return module('forum')->_show_error("You are not allowed to edit other users profile");
		}
		// Do save user info
		if (count($_POST)) {
			// In user signature we allow any code
			if (!empty($_POST["user_sig"])) {
				$_POST["user_sig"] = _prepare_html($_POST["user_sig"]);
			}
			// Some security fixes
			foreach ((array)$_POST as $k => $v) {
				$_POST[$k] = strip_tags($v);
			}
			// Upload avatar image
			if (strlen($_FILES["user_avatar"]["name"])) {
				// Delete previous avatar image
				if (strlen($user_info["user_avatar"])) {
					@unlink(REAL_PATH. module('forum')->SETTINGS["AVATARS_DIR"]. $user_info["user_avatar"]);
				}
				$img_file = REAL_PATH. module('forum')->SETTINGS["AVATARS_DIR"]. common()->rand_name(16). ".jpg";
				move_uploaded_file($_FILES["user_avatar"]["tmp_name"], $img_file);
				$I = &main()->init_class("resize_images", "classes/");
				$I->set_source($img_file);
				$I->set_limits(module('forum')->SETTINGS["AVATAR_MAX_X"], module('forum')->SETTINGS["AVATAR_MAX_Y"]);
				$I->save($img_file);
				// Create user avatar name for db query
				$user_avatar = substr($img_file, strlen(REAL_PATH.module('forum')->SETTINGS["AVATARS_DIR"]));
			}
			// Generate query
			$sql_array = array(
				"user_birth"	=> sprintf("%04d-%02d-%02d", $_POST["year_birth"], $_POST["month_birth"], $_POST["day_birth"]),
				"user_website"	=> _es($_POST["user_website"]),
				"user_icq"		=> _es($_POST["user_icq"]),
				"user_aim"		=> _es($_POST["user_aim"]),
				"user_yim"		=> _es($_POST["user_yim"]),
				"user_msnm"		=> _es($_POST["user_msnm"]),
				"user_from"		=> _es($_POST["user_location"]),
				"user_interests"=> _es($_POST["user_interests"]),
				"user_sig"		=> _es($_POST["user_sig"]),
			);
			if (!empty($user_avatar)) {
				$sql_array["user_avatar"]	= _es($user_avatar);
			}
			db()->UPDATE("forum_users", $sql_array, "`id`=".$user_info["id"]);
			// Redirect user
			return js_redirect(getenv("HTTP_REFERER"), false);
		} else {
			$replace = array(
				"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
				"birth_date_box"	=> $this->_box("user_birth", $user_info["user_birth"]),
				"user_website"		=> _prepare_html($user_info["user_website"]),
				"user_icq"			=> _prepare_html($user_info["user_icq"]),
				"user_aim"			=> _prepare_html($user_info["user_aim"]),
				"user_yim"			=> _prepare_html($user_info["user_yim"]),
				"user_msnm"			=> _prepare_html($user_info["user_msnm"]),
				"user_location"		=> _prepare_html($user_info["user_location"]),
				"user_interests"	=> _prepare_html($user_info["user_interests"]),
				"avatar_src"		=> !module('forum')->SETTINGS["USE_GLOBAL_USERS"] && !empty($user_info["user_avatar"]) ? WEB_PATH. module('forum')->SETTINGS["AVATARS_DIR"]. $user_info["user_avatar"] : "",
				"avatar_max_x"		=> intval(module('forum')->SETTINGS["AVATAR_MAX_X"]),
				"avatar_max_y"		=> intval(module('forum')->SETTINGS["AVATAR_MAX_Y"]),
				"avatar_max_bytes"	=> intval(module('forum')->SETTINGS["AVATAR_MAX_BYTES"]),
				"avatar_image_types"=> module('forum')->SETTINGS["AVATAR_IMAGE_TYPES"],
				"user_sig_with_bb"	=> $this->BB_OBJ->_process_text($user_info["user_sig"]),
				"user_sig"			=> _prepare_html($user_info["user_sig"]),
				"delete_avatar_link"=> "./?object=".FORUM_CLASS_NAME."&action=delete_avatar"._add_get(array("page")),
				"global_users_mode"	=> (int)(module('forum')->SETTINGS["USE_GLOBAL_USERS"]),
				"max_sig_length"	=> intval(module('forum')->SETTINGS["MAX_SIG_LENGTH"]),
			);
			$content = tpl()->parse(FORUM_CLASS_NAME."/user_cp/edit_profile", $replace);
			$body = $this->_user_cp_main_tpl($content);
		}
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Edit user settings
	*/
	function _edit_settings() {
		// Admin can edit any user account
		$_GET["id"] = intval($_GET["id"]);
		$user_id = FORUM_IS_ADMIN && !empty($_GET["id"]) ? $_GET["id"] : FORUM_USER_ID;
		// Get user info from db
		if (!empty($user_id)) {
			$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval($user_id));
		}
		// Try to create user's account (if we are in "global mode")
		if (empty($user_info["id"])) {
			$user_info = $this->_auto_create_user_profile();
		}
		// Last check user existance
		if (empty($user_info["id"])) {
			return module('forum')->_show_error("No such user!");
		}
		// Check access rights
		if ($is_own_profile && !module('forum')->USER_RIGHTS["edit_own_profile"]) {
			return module('forum')->_show_error("You are not allowed to edit own settings");
		}
		if (!FORUM_IS_ADMIN && !$is_own_profile && !module('forum')->USER_RIGHTS["edit_other_profile"]) {
			return module('forum')->_show_error("You are not allowed to edit other users settings");
		}
		// Load time zone module
		$TIME_ZONE_OBJ = main()->init_class("time_zone", "classes/");
		// Do save user settings
		if (count($_POST)) {
			$sql = "UPDATE `".db('forum_users')."` SET
					`view_sig`			= ".intval((bool) $_POST["view_sig"]).",
					`view_images`		= ".intval((bool) $_POST["view_images"]).",
					`view_avatars`		= ".intval((bool) $_POST["view_avatars"]).",
					`dst_status`		= ".intval((bool) $_POST["dst_status"]).",
					`posts_per_page`	= ".intval(in_array($_POST["posts_per_page"], $this->_posts_per_page) ? $_POST["posts_per_page"] : 0).",
					`topics_per_page`	= ".intval(in_array($_POST["topics_per_page"], $this->_topics_per_page) ? $_POST["topics_per_page"] : 0).",
					`user_timezone`		= '"._es(is_object($TIME_ZONE_OBJ) && array_key_exists($_POST["time_zone"], $TIME_ZONE_OBJ->_time_zones) ? $_POST["time_zone"] : 0)."'
				WHERE `id`=".$user_info["id"];
			db()->query($sql);
			// Redirect user
			js_redirect(getenv("HTTP_REFERER"), false);
		// Show form
		} else {
			$replace = array(
				"form_action"			=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
				"time_zone_box"			=> is_object($TIME_ZONE_OBJ) ? $TIME_ZONE_OBJ->_time_zone_box("time_zone", $user_info["user_timezone"]) : "",
				"dst_status"			=> intval($user_info["dst_status"]),
				"view_sig_box"			=> $this->_box("view_sig",			$user_info["view_sig"]),
				"view_images_box"		=> $this->_box("view_images",		$user_info["view_images"]),
				"view_avatars_box"		=> $this->_box("view_avatars",		$user_info["view_avatars"]),
				"posts_per_page_box"	=> $this->_box("posts_per_page",	$user_info["posts_per_page"]),
				"topics_per_page_box"	=> $this->_box("topics_per_page",	$user_info["topics_per_page"]),
				"user_time"				=> module('forum')->_show_date(),
			);
			$content = tpl()->parse(FORUM_CLASS_NAME."/user_cp/board_settings", $replace);
		}
		$body = $this->_user_cp_main_tpl($content);
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Menu for the user control panel
	*/
	function _user_cp_menu () {
		// Process template
		$replace = array(
			"edit_info_link"		=> "./?object=".FORUM_CLASS_NAME."&action=edit_profile"._add_get(array("page")),
			"board_settings_link"	=> "./?object=".FORUM_CLASS_NAME."&action=edit_settings"._add_get(array("page")),
			"subscr_topics_link"	=> module('forum')->SETTINGS["ALLOW_TRACK_TOPIC"] ? "./?object=".FORUM_CLASS_NAME."&action=tracker_manage_topics"._add_get(array("page")) : "",
			"subscr_forums_link"	=> module('forum')->SETTINGS["ALLOW_TRACK_FORUM"] ? "./?object=".FORUM_CLASS_NAME."&action=tracker_manage_forums"._add_get(array("page")) : "",
			"need_subscr_block"		=> (int)(module('forum')->SETTINGS["ALLOW_TRACK_TOPIC"] || module('forum')->SETTINGS["ALLOW_TRACK_FORUM"]),
			"announce_link"			=> FORUM_IS_ADMIN && module('forum')->SETTINGS["ALLOW_ANNOUNCES"] ? "./?object=".FORUM_CLASS_NAME."&action=edit_announces"._add_get(array("page")) : "",
		);
		return tpl()->parse(FORUM_CLASS_NAME."/user_cp/menu", $replace);
	}

	/**
	* Show Recent Read Topics
	*/
	function _show_recent_read_topics () {
		// Get last read topics
		if (module('forum')->SETTINGS["USE_READ_MESSAGES"]) {
			foreach ((array)$GLOBALS['forum_read_array'] as $_topic_id => $_info) {
//$topics_ids[$A["topic_id"]] = $A["read_date"]
// TODO: add order by date DESC
			}
//_show_topic_item
/*
			$Q = db()->query("SELECT * FROM `".db('forum_topics_read')."` WHERE `user_id`=".intval(FORUM_USER_ID)." ORDER BY `read_date` DESC LIMIT 10");
			while ($A = db()->fetch_assoc($Q)) $topics_ids[$A["topic_id"]] = $A["read_date"];
*/
		}
		// Get topics infos
		if (!empty($topics_ids)) {
			$Q = db()->query("SELECT * FROM `".db('forum_topics')."` WHERE `id` IN(".implode(",", array_keys($topics_ids)).") ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : ""));
			while ($topic_info = db()->fetch_assoc($Q)) $topics_array[$topic_info["id"]] = $topic_info;
		}
		// Try to find last posts in the current forum topics
		if (!empty($topics_array)) {
			list($last_posts, $topic_pages) = $this->_get_topics_last_posts_and_pages($topics_array);
		}
		// Init topic item object
		if (!empty($topics_array)) {
			$TOPIC_ITEM_OBJ = main()->init_class("forum_topic_item", FORUM_MODULES_DIR);
		}
		// Process posts
		if (!empty($topics_array) && is_object($TOPIC_ITEM_OBJ)) {
			foreach ((array)$topics_ids as $topic_id => $topic_date) {
				$topic_info = &$topics_array[$topic_id];
				if (empty($topic_info)) {
					continue;
				}
				$topic_is_moved = intval(!empty($topic_info["moved_to"]));
				$moved_id = $topic_is_moved ? array_pop(explode(",", $topic_info["moved_to"])) : 0;
				$body	.= $TOPIC_ITEM_OBJ->_show_topic_item($topic_info, 1, $last_posts[$moved_id ? $moved_id : $topic_info["id"]], $topic_pages[$topic_info["id"]], "/user_cp/recent_topics_item");
			}
		}
		return $body;
	}

	/**
	* Get Topics Last Posts And Pages
	*/
	function _get_topics_last_posts_and_pages($topics_array = array()) {
		if (!is_array($topics_array)) {
			return false;
		}
		$last_posts_ids		= array();
		$topic_pages_ids	= array();
		foreach ((array)$topics_array as $topic_info) {
			// Skip empty topics
			if (empty($topic_info["last_post_id"])) {
				continue;
			}
			$last_posts_ids[$topic_info["last_post_id"]] = $topic_info["last_post_id"];
			// Skip next action if topics pages not needed
			if (!module('forum')->SETTINGS["SHOW_TOPIC_PAGES"]) {
				continue;
			}
			// Check if need to process topic pages
			if ($topic_info["num_posts"] > module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"]) {
				$topic_pages_ids[$topic_info["id"]] = $topic_info["num_posts"];
			}
		}
		// Process last posts records
		if (!empty($last_posts_ids)) {
			$last_posts = array();
			$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `id` IN(".implode(",",$last_posts_ids).")");
			while ($post_info = db()->fetch_assoc($Q)) {

				$subject = strlen($post_info["subject"]) ? $post_info["subject"] : $post_info["text"];
				$subject = module('forum')->_cut_subject_for_last_post($subject);

				$replace3 = array(
					"last_post_author_name"	=> !empty($post_info["user_name"]) ? _prepare_html($post_info["user_name"]) : t("Anonymous"),
					"last_post_author_link"	=> $post_info["user_id"] ? module('forum')->_user_profile_link($post_info["user_id"]) : "",
					"last_post_subject"		=> _prepare_html($subject),
					"last_post_date"		=> module('forum')->_show_date($post_info["created"], "last_post_date"),
					"last_post_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$post_info["topic"]._add_get(array("page")),
				);
				$last_posts[$post_info["topic"]] = tpl()->parse(FORUM_CLASS_NAME."/view_forum_last_posts", $replace3);
			}
		}
		// Process topic pages
		if (module('forum')->SETTINGS["SHOW_TOPIC_PAGES"] && !empty($topic_pages_ids)) {
			$topic_pages = array();
			foreach ((array)$topic_pages_ids as $topic_id => $topic_num_posts) {
				list(,$topic_pages[$topic_id],) = common()->divide_pages("", "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$topic_id, null, module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"], $topic_num_posts, FORUM_CLASS_NAME."/pages_2/");
			}
		}
		return array($last_posts, $topic_pages);
	}

	/**
	* Delete avatar
	*/
	function _delete_avatar() {
		if (!FORUM_USER_ID) {
			return module('forum')->_show_error(t("you_are_not_logged_in"));
		}
		// Admin can edit any user account
		$_GET["id"] = intval($_GET["id"]);
		$user_id = FORUM_IS_ADMIN && !empty($_GET["id"]) ? $_GET["id"] : FORUM_USER_ID;
		// Get user info from db
		if (!empty($user_id)) {
			$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval($user_id));
		}
		// Check user existance
		if (empty($user_info["id"])) {
			return module('forum')->_show_error("No such user!");
		}
		// Delete previous avatar image
		if (!empty($user_info["user_avatar"])) {
			@unlink(REAL_PATH. module('forum')->SETTINGS["AVATARS_DIR"]. $user_info["user_avatar"]);
			db()->query("UPDATE `".db('forum_users')."` SET `user_avatar`='' WHERE `id`=".$user_info["id"]);
		}
		// Redirect user
		js_redirect(getenv("HTTP_REFERER"), false);
	}

	/**
	* Delete user's profile
	*/
	function _delete_profile () {
		$_GET["id"] = intval($_GET["id"]);
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			return module('forum')->_show_error("Disabled by the site admin!");
		}
		if (!FORUM_IS_ADMIN) {
			return module('forum')->_show_error("You have no rights for this action!");
		}
		// Get user info
		if (!empty($user_id)) {
			$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".$_GET["id"]);
		}
		// Do delete (avoid deleting admin account)
		if (!empty($user_info["id"]) && $user_info["group"] != 1) {
			db()->query("DELETE FROM `".db('forum_users')."` WHERE `id`=".intval($user_info["id"]));
		}
		// Redirect user
		js_redirect("./?object=".FORUM_CLASS_NAME);
	}

	/**
	* Save user info
	*/
	function _create_new_user ($user_info = array()) {
// FIXME: need to unify with the next method
		db()->INSERT("forum_users", array(
			"user_email"	=> _es($user_info["email"]),
			"name"			=> _es($user_info["login"]),
			"pswd"			=> md5($user_info["pswd"]),
			"user_timezone"	=> _es(is_object($TIME_ZONE_OBJ) && array_key_exists($_POST["time_zone"], $TIME_ZONE_OBJ->_time_zones) ? $user_info["time_zone"] : 0),
			"dst_status"	=> intval((bool) $user_info["dst_status"]),
			"user_regdate"	=> time(),
		));
	}

	/**
	* Auto create user's profile (if we are in "global" mode)
	*
	* @access	private
	* @param	$this->USER_ID
	* @return	mixed	array if success, false otherwise
	*/
	function _auto_create_user_profile () {
		// Check mode
		if (!module('forum')->SETTINGS["USE_GLOBAL_USERS"] || !main()->USER_ID) {
			return false;
		}
		// Check if such user already exists
		$user_info = db()->query_fetch("SELECT `id` FROM `".db('forum_users')."` WHERE `id`=".intval(main()->USER_ID));
		if (!empty($user_info["id"])) {
			return false;
		}
		// Do create profile
		db()->INSERT("forum_users", array(
			"id"			=> intval(main()->USER_ID),
//			"user_email"	=> _es($user_info["email"]),
//			"name"			=> _es($user_info["login"]),
			"user_timezone"	=> floatval(0),
			"dst_status"	=> intval((bool) $user_info["dst_status"]),
			"user_regdate"	=> time(),
		));
		// Return result array
		return db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval(main()->USER_ID)." LIMIT 1");
	}

	/**
	* Retrieve forgotten password
	*/
	function _send_password () {
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			return module('forum')->_show_error("Disabled by the site admin!");
		}
		// Check if need to use captcha
		$use_captcha = module('forum')->SETTINGS["USE_CAPTCHA"] && is_object(module('forum')->CAPTCHA);
		// Process posted data
		if (count($_POST) && !empty($_POST["login"])) {
			// Validate captcha
			if ($use_captcha) {
				module('forum')->CAPTCHA->check("captcha_code");
			}
			// Show error message if exists
			if (!common()->_error_exists()) {
/*
				$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `name`='"._es(trim($_POST["login"]))."' AND `user_email`='"._es(trim($_POST["email"]))."' AND `status`='a' LIMIT 1");
				if (!empty($user_info['id'])) {
					$replace = array(
						"login"			=> $user_info["name"],
						"password"		=> $user_info["pswd"],
					);
					$text = tpl()->parse(FORUM_CLASS_NAME."/send_pswd/email", $replace);
					// Send email with user password
					common()->send_mail(module('forum')->SETTINGS["ADMIN_EMAIL_FROM"], t("administator")." ".conf('website_name'), $user_info["user_email"], $user_info["user_email"], t("Lost password"), $text, $text);
					$body .= t("password_sent");
				} else return module('forum')->_show_error("No such user in database!");
*/
				return module('forum')->_show_error("Not done yet!");
// TODO
			} else {
				return module('forum')->_show_error(_e());
			}
		// Show form
		} else {
			$replace = array(
				"form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"],
				"use_captcha"	=> intval($use_captcha),
				"captcha_image"	=> $use_captcha ? module('forum')->CAPTCHA->show_html("./?object=".FORUM_CLASS_NAME."&action=show_captcha_image") : "",
				"captcha_hash"	=> /*$use_captcha ? $_SESSION[module('forum')->CAPTCHA->var_name] : */"",
			);
			$body .= tpl()->parse(FORUM_CLASS_NAME."/send_pswd/main_form", $replace);
		}
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Register funuction
	*/
	function _register () {
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			return module('forum')->_show_error("Disabled by the site admin!");
		}
		// Load time zone module
		$TIME_ZONE_OBJ = main()->init_class("time_zone", "classes/");
		// Check if need to use captcha
		$use_captcha = module('forum')->SETTINGS["USE_CAPTCHA"] && is_object(module('forum')->CAPTCHA);
		// Process post data
		if (count($_POST) && !empty($_POST["login"])) {
			// Check required fields
			if (!empty($_POST["login"]) && (_strlen($_POST["login"]) > module('forum')->SETTINGS["MAX_USER_NAME"] || _strlen($_POST["login"]) < module('forum')->SETTINGS["MIN_USER_NAME"])) {
				common()->_raise_error(t("Wrong login length"));
			}
			if (!empty($_POST["login"]) && db()->query_num_rows("SELECT `id` FROM `".db('forum_users')."` WHERE `name`='"._es($_POST["login"])."'")) {
				common()->_raise_error(t("login_exists")." \"".$_POST["login"]."\"");
			}
			if (!common()->email_verify($_POST["email"])) {
				common()->_raise_error(t("wrong_email"));
			}
			if (!strlen($_POST["pswd"]) || $_POST["pswd"] != $_POST["pswd2"]) {
				common()->_raise_error(t("wrong_password"));
			}
			// Validate captcha
			if ($use_captcha) {
				module('forum')->CAPTCHA->check("captcha_code");
			}
			// Show error message if exists
			if (!common()->_error_exists()) {
				// Send confirmation email if needed
				if (module('forum')->SETTINGS["CONFIRM_REGISTER"]) {
					$E = main()->init_class("encryption", "classes/");
					if (!empty(module('forum')->SETTINGS["SECRET_KEY"])) {
						$E->set_key(module('forum')->SETTINGS["SECRET_KEY"]);
					}
					$confirm_string = $E->_safe_encrypt_with_base64(time()."##".$_POST["email"]."##".$_POST["login"]."##".$_POST["pswd"]);
					// Get message template
					$replace2 = array(
						"link" => process_url("./?object=".FORUM_CLASS_NAME."&action=confirm_register&key=".$confirm_string),
					);
					$text = tpl()->parse(FORUM_CLASS_NAME."/register/confirm_email", $replace2);
					// Send confirmation email
					common()->send_mail(module('forum')->SETTINGS["ADMIN_EMAIL_FROM"], "administrator", $_POST["email"], "Forum new user", "Confirm registration", $text, $text);
					// Show result message
					$replace = array(
					);
					$body = tpl()->parse(FORUM_CLASS_NAME."/register/email_sent", $replace);
				} else {
					$user_info = array(
						"email"			=>	$_POST["email"],
						"login"			=>	$_POST["login"],
						"pswd"			=>	$_POST["pswd"],
						"time_zone"		=>	$_POST["time_zone"],
						"dst_status"	=>	$_POST["dst_status"],
					);
					$this->_create_new_user($user_info);
					// Show result message
					$replace = array(
					);
					$body = tpl()->parse(FORUM_CLASS_NAME."/register/success", $replace);
				}
			} else {
				return module('forum')->_show_error(_e());
			}
		// Show form
		} else {
			$replace = array(
				"form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"],
				"time_zone_box"	=> is_object($TIME_ZONE_OBJ) ? $TIME_ZONE_OBJ->_time_zone_box("time_zone", $user_info["user_timezone"]) : "",
				"use_captcha"	=> intval($use_captcha),
				"captcha_image"	=> $use_captcha ? module('forum')->CAPTCHA->show_html("./?object=".FORUM_CLASS_NAME."&action=show_captcha_image") : "",
				"captcha_hash"	=> /*$use_captcha ? $_SESSION[module('forum')->CAPTCHA->var_name] : */"",
			);
			$body = tpl()->parse(FORUM_CLASS_NAME."/register/main_form", $replace);
		}
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Confirm registration function
	*/
	function _confirm_register () {
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			return module('forum')->_show_error("Disabled by the site admin!");
		}
		if (!empty($_GET["key"]) && module('forum')->SETTINGS["CONFIRM_REGISTER"]) {
			$E = main()->init_class("encryption", "classes/");
			if (!empty(module('forum')->SETTINGS["SECRET_KEY"])) {
				$E->set_key(module('forum')->SETTINGS["SECRET_KEY"]);
			}
			list($created, $email, $login, $pswd) = explode("##", $E->_safe_decrypt_with_base64($_GET["key"]));
			// Check required fields
			if (strlen($created) && strlen($email) && strlen($login) && strlen($pswd)) {
				if ($created < (time() - module('forum')->SETTINGS["REGISTRATION_TTL"])) {
					common()->_raise_error(t("link_has_expired"));
				}
				if (db()->query_num_rows("SELECT `id` FROM `".db('forum_users')."` WHERE `name`='"._es($login)."'")) {
					common()->_raise_error(t("login_exists")." \"".$login."\"");
				}
				// Show error message if exists
				if (!common()->_error_exists()) {
					$user_info = array(
						"email"	=>	$email,
						"login"	=>	$login,
						"pswd"	=>	$pswd,
					);
					$this->_create_new_user($user_info);
					$replace = array(
					);
					$body = tpl()->parse(FORUM_CLASS_NAME."/register/success", $replace);
				} else {
					return module('forum')->_show_error(_e());
				}
			} else {
				return module('forum')->_show_error("Wrong code!");
			}
		}
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Email user
	*/
	function _email_user () {
// TODO
		$content = t("Email user will be here...");
		$body = $this->_user_cp_main_tpl($content);
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Main template for the user control panel (user_cp)
	*/
	function _user_cp_main_tpl ($content = "") {
		$replace = array(
			"is_admin"		=> intval(FORUM_IS_ADMIN),
			"user_regdate"	=> date($this->format["date"], $replace["user_regdate"]),
			"board_fast_nav"=> module('forum')->SETTINGS["ALLOW_FAST_JUMP_BOX"] ? module('forum')->_board_fast_nav_box() : "",
			"menu"			=> $this->_user_cp_menu(),
			"content"		=> $content,
		);
		return tpl()->parse(FORUM_CLASS_NAME."/user_cp/main", $replace);
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
