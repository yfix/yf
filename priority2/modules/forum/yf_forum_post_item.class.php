<?php

/**
* Unified display post module
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_post_item {

	/**
	* Constructor
	*/
	function _init () {
		// Init bb codes module
		$this->BB_OBJ = main()->init_class("bb_codes", "classes/");
		// Get online users ids for those who posted here
		foreach ((array)module("forum")->online_array as $online_info) {
			if (!empty($online_info["user_id"]) && !empty($this->_users_array[$online_info["user_id"]])) {
				$this->_online_users_ids[$online_info["user_id"]] = $online_info["user_id"];
			}
		}
	}

	/**
	* Show Post Item
	*/
	function _show_post_item($post_info = array(), $user_info = array(), $topic_info = array(), $stpl_name = "", $not_first_post = 0, $allow_reply = 1) {
		static $i = 1;
		// Default template
		if (empty($stpl_name)) {
			$stpl_name = "/view_topic_flat/post_item";
		}
		$user_is_online = !empty($this->_online_users_ids[$post_info["user_id"]]);
		// Get user group
		$user_group = $post_info["user_id"] ? t($this->FORUM_USER_GROUPS[$user_info["group"]]) : "";
		// Process post template
		$show_sig = FORUM_USER_ID && !module('forum')->USER_SETTINGS["VIEW_SIG"] ? 0 : 1;
		if (!$post_info["use_sig"]) {
			$show_sig = 0;
		}
		// Check rights
		$is_own_topic	= FORUM_USER_ID && $topic_info["user_id"] && FORUM_USER_ID == $topic_info["user_id"];
		$is_own_post	= FORUM_USER_ID && $post_info["user_id"] && FORUM_USER_ID == $post_info["user_id"];
		$allow_edit		= FORUM_IS_ADMIN || ($is_own_post && module('forum')->USER_RIGHTS["edit_own_posts"]) || (!$is_own_post && module('forum')->USER_RIGHTS["edit_other_posts"]);
		$allow_delete	= $not_first_post && (FORUM_IS_ADMIN || ($is_own_post && module('forum')->USER_RIGHTS["delete_own_posts"]) || (!$is_own_post && module('forum')->USER_RIGHTS["delete_other_posts"]));
		$allow_email	= FORUM_IS_ADMIN && $post_info["user_id"];
		// Deny guests posting (if needed)
		if (!FORUM_USER_ID && !module('forum')->SETTINGS["ALLOW_GUESTS_POSTS"]) {
			$allow_reply		= 0;
			$allow_new_topic	= 0;
		}
		if (module('forum')->SETTINGS["ALLOW_ATTACHES"]) {
			$attach_path	= module('forum')->_get_attach_path($post_info["id"]);
		}
		// Process template
		$replace = array(
			"is_admin"			=> intval(FORUM_IS_ADMIN),
			"is_moderator"		=> intval(FORUM_IS_ADMIN || (FORUM_IS_MODERATOR && module('forum')->_moderate_forum_allowed($post_info["forum"]))),
			"td_class"			=> !($i++ % 2) ? module('forum')->_CSS["show1"] : module('forum')->_CSS["show2"],
			"css_class_1"		=> module('forum')->_CSS["post_".($post_info["status"] == "a" ? "a" : "u")."_1"],
			"post_num"			=> $i,
			"post_id"			=> intval($post_info["id"]),
			"post_date"			=> module('forum')->_show_date($post_info["created"], "post_date"),
// FIXME: smilies incorrect
			"post_text"			=> $this->BB_OBJ->_process_text($post_info["text"], !$post_info["use_emo"]),
			"user_details"		=> module('forum')->_show_user_details($user_info, $user_is_online, $post_info["user_name"], $post_info["id"]),
			"user_id"			=> intval($post_info["user_id"]),
			"user_name"			=> strlen($post_info["user_name"]) ? $post_info["user_name"] : (strlen($user_info["name"]) ? $user_info["name"] : t("Anonymous")),
			"user_profile_link"	=> $post_info["user_id"] ? module('forum')->_user_profile_link($post_info["user_id"]) : "",
			"user_group"		=> $post_info["user_id"] ? $user_group : "",
			"user_sig"			=> $post_info["user_id"] && $show_sig ? $this->BB_OBJ->_process_text($user_info["user_sig"]) : "",
			"user_is_online"	=> $post_info["user_id"] && module('forum')->SETTINGS["ONLINE_USERS_STATS"] ? $user_is_online : "",
			"user_is_offline"	=> $post_info["user_id"] && module('forum')->SETTINGS["ONLINE_USERS_STATS"] ? !$user_is_online : "",
			"ip_address"		=> module('forum')->USER_RIGHTS["view_ip"] ? $post_info["poster_ip"] : "",
			"quote_link"		=> $allow_reply		? "./?object=".FORUM_CLASS_NAME."&action=reply&id=".$post_info["id"]._add_get(array("page")) : "",
			"no_quote_link"		=> $allow_reply		? "./?object=".FORUM_CLASS_NAME."&action=reply_no_quote&id=".$post_info["id"]._add_get(array("page")) : "",
			"email_link"		=> $allow_email		? "./?object=".FORUM_CLASS_NAME."&action=email_user&id=".$post_info["user_id"]._add_get(array("page")) : "",
			"edit_link"			=> $allow_edit		? "./?object=".FORUM_CLASS_NAME."&action=edit_post&id=".$post_info["id"]._add_get(array("page")) : "",
			"delete_link"		=> $allow_delete	? "./?object=".FORUM_CLASS_NAME."&action=delete_post&id=".$post_info["id"]._add_get(array("page")) : "",
			"report_link"		=> FORUM_USER_ID ? process_url("./?object=".FORUM_CLASS_NAME."&action=report_post&id=".$post_info["id"]._add_get()) : "",
			"show_edit_by"		=> intval($post_info["show_edit_by"] && $post_info["edit_time"] && !empty($post_info["edit_name"])),
			"editor_name"		=> $post_info["edit_name"],
			"edit_time"			=> module('forum')->_show_date($post_info["edit_time"], "edit_time"),
			"attach_image_src"	=> !empty($attach_path) && file_exists(INCLUDE_PATH. $attach_path) ? WEB_PATH. $attach_path : "",
			"is_last_post"		=> intval(module('forum')->_topic_info["last_post_id"] == $post_info["id"]),
		);
		// Additional fields for the search results
		if (!empty($topic_info)) $replace = array_merge($replace, array(
			"topic_name"		=> _prepare_html($topic_info["name"]),
			"forum_name"		=> _prepare_html(module('forum')->_forums_array[$post_info["forum"]]["name"]),
			"num_replies"		=> intval($topic_info["num_posts"]),
			"num_views"			=> intval($topic_info["num_views"]),
			"forum_link"		=> module('forum')->_link_to_forum($post_info["forum"]),
			"topic_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$post_info["topic"]._add_get(array("page")),
			"post_link"			=> "./?object=".FORUM_CLASS_NAME."&action=view_post&id=".$post_info["id"]._add_get(array("page")),
		));
		return tpl()->parse(FORUM_CLASS_NAME. $stpl_name, $replace);
	}
}
