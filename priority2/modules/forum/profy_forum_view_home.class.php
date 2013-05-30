<?php

/**
* Show home page for the forum
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_forum_view_home {

	/**
	* Show Main
	*/
	function _show_main () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$cat_id = $_GET["id"];
		}
		// Reference to the categories array
		$cats_array = &module('forum')->_forum_cats_array;
		// Reference to the forums array
		$forums_array = &module('forum')->_forums_array;
		// Get last forum posts
		foreach ((array)$forums_array as $forum_info) {
			// Skip empty forums
			if (empty($forum_info["last_post_id"])) {
				continue;
			}
			// Skip inactive forums
			if ($forum_info["status"] != "a") {
				continue;
			}
			// Filter category if specified one
			if (!empty($cat_id) && $forum_info["category"] != $cat_id) {
				continue;
			}
			// Check user group access rights to the current forum
			if ($forum_info["user_groups"]) {
				$only_for_groups = $forum_info["user_groups"] ? explode(",", $forum_info["user_groups"]) : "";
				if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
					$this->_skip_forums[$forum_info["id"]] = $forum_info["id"];
					continue;
				}
			}
			$last_posts_ids[$forum_info["last_post_id"]] = $forum_info["last_post_id"];
		}
		$posts_per_page = !empty(module('forum')->USER_SETTINGS["POSTS_PER_PAGE"]) ? module('forum')->USER_SETTINGS["POSTS_PER_PAGE"] : module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"];
		// Process last posts records
		if (!empty($last_posts_ids)) {
			$last_posts_array = main()->get_data("forum_home_page_posts");
			$this->_last_posts = array();
			// Process last posts records
			foreach ((array)$last_posts_array as $post_info) {
				// Do not remove! (need while using cache)
				if (!in_array($post_info["id"], $last_posts_ids)) {
					continue;
				}
				if (isset($this->_skip_forums[$post_info["forum"]])) {
					continue;
				}

				$subject = strlen($post_info["subject"]) ? $post_info["subject"] : $post_info["text"];
				$subject = module('forum')->_cut_subject_for_last_post($subject);

				$_num_pages = ceil(($post_info["total_posts"] + 1) / $posts_per_page);
				$replace3 = array(
					"last_post_author_name"	=> !empty($post_info["user_name"]) ? _prepare_html($post_info["user_name"]) : t("Anonymous"),
					"last_post_author_link"	=> $post_info["user_id"] ? module('forum')->_user_profile_link($post_info["user_id"]) : "",
					"last_post_subject"		=> _prepare_html($subject),
					"last_post_date"		=> module('forum')->_show_date($post_info["created"], "last_post_date"),
					"last_post_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$post_info["topic"].($_num_pages > 1 ? "&page=".$_num_pages : "")."#last_post",
					"user_id"				=> intval($post_info["user_id"]),
				);
				$this->_last_posts[$post_info["forum"]] = tpl()->parse(FORUM_CLASS_NAME."/view_home_last_posts", $replace3);
				$this->_topics_ids[$post_info["topic"]] = $post_info["forum"];
			}
		}
		// Get moderators for the forums
		$this->_moderators_by_forums = $this->_get_moderators();
		// Process categories
		foreach ((array)$cats_array as $cat_info) {
			$forums = "";
			// Skip non-active categories
			if ($cat_info["status"] != "a") {
				continue;
			}
			// Filter category if specified one
			if (!empty($cat_id) && $cat_info["id"] != $cat_id) {
				continue;
			}
			// Process forums
			foreach ((array)$forums_array as $_forum_info) {
				// Skip forums from other categories
				if ($_forum_info["category"] != $cat_info["id"]) {
					continue;
				}
				// Skip inactive forums
				if ($_forum_info["status"] != "a") {
					continue;
				}
				// Skip sub-forums here
				if (!empty($_forum_info["parent"])) {
					continue;
				}
				$forums .= $this->_show_forum_item($_forum_info);
			}
			// Category template
			$replace2 = array(
				"cat_id"			=> _prepare_html($cat_info["id"]),
				"cat_name"			=> _prepare_html($cat_info["name"]),
				"cat_link"			=> "./?object=".FORUM_CLASS_NAME."&id=".$cat_info["id"]._add_get(array("id")),
				"forums"			=> $forums,
				"show_cat_contents"	=> module('forum')->SETTINGS["SHOW_EMPTY_CATS"] ? 1 : (empty($forums) ? 0 : 1),
			);
			$forum_cats .= tpl()->parse(FORUM_CLASS_NAME."/view_home_cat_item", $replace2);
		}
		// Home page template
		$replace = array(
			"is_admin"			=> intval(FORUM_IS_ADMIN),
			"logged_in"			=> intval(FORUM_USER_ID),
			"last_visit_date"	=> FORUM_USER_ID && FORUM_USER_LAST_VISIT ? module('forum')->_show_date(FORUM_USER_LAST_VISIT, "user_last_visit") : "",
			"search_form_action"=> "./?object=".FORUM_CLASS_NAME."&action=search". _add_get(),
			"login_form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=login". _add_get(),
			"cats"				=> $forum_cats,
			"rss_board_button"	=> module('forum')->_show_rss_link("./?object=".FORUM_CLASS_NAME."&action=rss_board", "RSS feed for board"),
			"sync_board_link"	=> FORUM_IS_ADMIN ? "./?object=".FORUM_CLASS_NAME."&action=sync_board". _add_get() : "",
		);
		return !empty($forum_cats) ? module('forum')->_show_main_tpl(tpl()->parse(FORUM_CLASS_NAME."/view_home_main", $replace)) : module('forum')->_show_error($_GET["id"] ? t("No such category") : t("no_active_categories"));
	}

	/**
	* Show forum item
	*/
	function _show_forum_item ($forum_info = array()) {
		$sub_forums = array();
		foreach ((array)module('forum')->_get_sub_forums_ids($forum_info["id"], 1) as $_sub_id) {
			$_sub_info = module('forum')->_forums_array[$_sub_id];
			$sub_forums[$_sub_id] = array(
				"name"			=> $_sub_info["name"],
				"desc"			=> $_sub_info["desc"],
				"num_topics"	=> $_sub_info["num_topics"],
				"num_posts"		=> $_sub_info["num_posts"],
				"num_views"		=> $_sub_info["num_views"],
				"is_active"		=> $_sub_info["status"] == "a" ? 1 : 0,
				"view_link"		=> module('forum')->_link_to_forum($_sub_info['id']),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_forum&id=".$_sub_id,
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_forum&id=".$_sub_id,
			);
		}
		$is_private = isset($this->_skip_forums[$forum_info["id"]]);
		// Prepare template
		$replace = array(
			"is_admin"			=> intval(FORUM_IS_ADMIN),
			"td_class"			=> !($i++ % 2) ? module('forum')->_CSS["show1"] : module('forum')->_CSS["show2"],
			"forum_status_icon"	=> $this->_show_forum_new_msgs_status($forum_info),
			"forum_link"		=> !$is_private ? module('forum')->_link_to_forum($forum_info["id"]) : "",
			"forum_name"		=> _prepare_html($forum_info["name"]),
			"forum_desc"		=> _prepare_html($forum_info["desc"]),
			"forum_last_post"	=> $this->_last_posts[$forum_info["id"]],
			"forum_num_topics"	=> $forum_info["num_topics"],
			"forum_num_posts"	=> $forum_info["num_posts"],
			"moderators_list"	=> $this->_moderators_by_forums[$forum_info["id"]],
			"rss_forum_button"	=> module('forum')->_show_rss_link("./?object=".FORUM_CLASS_NAME."&action=rss_forum&id=".$forum_info["id"], "RSS feed for forum: ".$forum_info["name"]),
			"sub_forums"		=> $sub_forums,
			"has_sub_forums"	=> empty($sub_forums) ? 0 : 1,
			"is_private"		=> intval((bool)$is_private),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/view_home_forum_item", $replace);
	}

	/**
	* Show forum read/unread messages status for the current user
	*/
	function _show_forum_new_msgs_status ($forum_info = array()) {
		// Forum is read-only
		if ($forum_info["status"] == "r") {
			$forum_status = 2;
		} else {
			// No new messages by default
			$forum_status = 0;
			// Try to find read messages
			if (module('forum')->SETTINGS["USE_READ_MESSAGES"] && !module('forum')->_get_forum_read($forum_info)) {
				$forum_status = 1;
			}
		}
		$replace = array(
			"img_src"		=> WEB_PATH. tpl()->TPL_PATH. module('forum')->FORUM_STATUSES[$forum_status][0],
			"status_title"	=> t(module('forum')->FORUM_STATUSES[$forum_status][1]),
			"status_id"		=> intval($forum_status),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/view_home_status_icon", $replace);
	}

	/**
	* Get moderators fro the forums
	*/
	function _get_moderators () {
		if (!is_array(module('forum')->_forum_moderators)) {
			return false;
		}
		foreach ((array)module('forum')->_forum_moderators as $moderator_info) {
			$replace = array(
				"user_profile_link"	=> module('forum')->_user_profile_link($moderator_info["member_id"]),
				"user_name"			=> _prepare_html($moderator_info["member_name"]),
			);
			foreach (explode(",", $moderator_info["forums_list"]) as $_forum_id) {
				$mods_array[$_forum_id][$moderator_info["member_id"]] = tpl()->parse(FORUM_CLASS_NAME."/view_home_moderator_item", $replace);
			}
		}
		foreach ((array)$mods_array as $forum_id => $moderators_infos) {
			$moderators_by_forums[$forum_id] = implode(", ", $moderators_infos);
		}
		return $moderators_by_forums;
	}
}
