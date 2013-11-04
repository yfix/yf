<?php

/**
* Unified display topic item module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_topic_item {

	/**
	* Show Topic Item
	*/
	function _show_topic_item ($topic_info = array(), $topic_is_read = 0, $last_post = array(), $topic_pages = "", $stpl_name = "") {
		static $i = 1;
		// Default template name
		if (empty($stpl_name)) {
			$stpl_name = "/view_forum_topic_item";
		}
		$topic_is_moved = intval(!empty($topic_info["moved_to"]));
		$moved_id = $topic_is_moved ? array_pop(explode(",", $topic_info["moved_to"])) : 0;
		$replace = array(
			"is_admin"			=> intval(FORUM_IS_ADMIN),
			"is_moderator"		=> intval(FORUM_IS_ADMIN || (FORUM_IS_MODERATOR && module('forum')->_moderate_forum_allowed($topic_info["forum"]))),
			"td_class"			=> !($i++ % 2) ? module('forum')->_CSS["show1"] : module('forum')->_CSS["show2"],
			"css_class_1"		=> module('forum')->_CSS["topic_".($topic_info["approved"] ? "a" : "u")."_1"],
			"css_class_2"		=> module('forum')->_CSS["topic_".($topic_info["approved"] ? "a" : "u")."_2"],
			"topic_status_icon"	=> $this->_show_topic_new_msgs_status($topic_info),
			"topic_icon"		=> $topic_info["icon_id"] ? WEB_PATH. module('forum')->SETTINGS["POST_ICONS_DIR"]. $topic_info["icon_id"].".gif" : "",
			"topic_id"			=> $topic_info["id"],
			"topic_link"		=> "./?object=".'forum'."&action=view_topic&id=".($moved_id ? $moved_id : $topic_info["id"])._add_get(array("page")),
			"topic_name"		=> ($topic_is_moved ? t("Moved").": " : ""). _prepare_html($topic_info["name"]),
			"topic_desc"		=> _prepare_html($topic_info["desc"]),
			"topic_pages"		=> $topic_pages,
			"topic_author_name"	=> strlen($topic_info["user_name"]) ? _prepare_html($topic_info["user_name"]) : t("Anonymous"),
			"topic_author_link"	=> $topic_info["user_id"] ? module('forum')->_user_profile_link($topic_info["user_id"]) : "",
			"topic_num_posts"	=> $topic_is_moved ? "--" : $topic_info["num_posts"],
			"topic_num_views"	=> $topic_is_moved ? "--" : $topic_info["num_views"],
			"topic_start_date"	=> module('forum')->_show_date($topic_info["created"], "topic_start_date"),
			"topic_last_post"	=> $last_post,
			"topic_approved"	=> intval($topic_info["approved"]),
			"forum_name"		=> _prepare_html(module('forum')->_forums_array[$topic_info["forum"]]["name"]),
			"forum_link"		=> module('forum')->_link_to_forum($topic_info["forum"]),
			"rss_topic_button"	=> module('forum')->_show_rss_link("./?object=".'forum'."&action=rss_forum&id=".$topic_info["forum"], "RSS feed for topic: ".$topic_info["name"]),
			"user_id"			=> intval($topic_info["user_id"]),
			"fast_view_replies"	=> (int)module('forum')->SETTINGS["FAST_VIEW_REPLIERS"],
			"fast_text_preview"	=> (int)module('forum')->SETTINGS["FAST_TEXT_PREVIEW"],
			"first_post_id"		=> $topic_info["first_post_id"],
		);
		return tpl()->parse('forum'. $stpl_name, $replace);
	}

	/**
	* Show topic read/unread messages status for the current user
	*/
	function _show_topic_new_msgs_status ($topic_info = array()) {
		$topic_is_read = module('forum')->SETTINGS["USE_READ_MESSAGES"] ? module('forum')->_get_topic_read($topic_info) : 0;
		// Topic is closed
		if (!empty($topic_info["moved_to"])) {
			$topic_status = 4;
		} elseif ($topic_info["status"] != "a")	{
			$topic_status = 5;
		} else {
			// Check if topic is hot
			$topic_is_hot = $topic_info["num_posts"] >= module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"];
			$topic_status = $topic_is_hot ? 2 : 0;
			// For the looged in user check for new messages
			if (FORUM_USER_ID && module('forum')->SETTINGS["USE_READ_MESSAGES"] && !$topic_is_read) {
				$topic_status = $topic_is_hot ? 3 : 1;
			}
		}
		$replace = array(
			"img_src"		=> WEB_PATH. tpl()->TPL_PATH. module('forum')->TOPIC_STATUSES[$topic_status][0],
			"status_title"	=> t(module('forum')->TOPIC_STATUSES[$topic_status][1]),
		);
		return tpl()->parse('forum'."/view_forum_status_icon", $replace);
	}
}
