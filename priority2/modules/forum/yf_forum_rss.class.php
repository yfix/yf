<?php

/**
* Board RSS methods here
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_rss {
	
	/**
	* Display latest posts inside whole board as RSS feed
	*/
	function _display_for_board() {
		// Stop here if RSS export is turned off
		if (empty(module('forum')->SETTINGS["RSS_EXPORT"])) {
			return module('forum')->_show_error("RSS export is disabled!");
		}
		// Get latest posts
		$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `status`='a' ORDER BY `created` DESC LIMIT ".intval(!empty(module('forum')->SETTINGS["RSS_LATEST_IN_BOARD"]) ? module('forum')->SETTINGS["RSS_LATEST_IN_BOARD"] : 15));
		while ($A = db()->fetch_assoc($Q)) {
			$data[] = array(
				"title"			=> _prepare_html($A["subject"], 0),
				"link"			=> process_url("./?object=".FORUM_CLASS_NAME."&action=view_post&id=".$A["id"]),
				"description"	=> _prepare_html(module('forum')->_cut_bb_codes($A["text"]), 0),
				"date"			=> $A["created"],
				"author"		=> _prepare_html($A["user_name"], 0),
				"source"		=> "",
			);
		}
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_board",
			"feed_title"		=> "Latest posts inside board",
			"feed_desc"			=> "Latest posts inside board",
			"feed_url"			=> process_url("./?object=".FORUM_CLASS_NAME),
		);
		return common()->rss_page($data, $params);
	}
	
	/**
	* Display latest posts inside forum as RSS feed
	*/
	function _display_for_forum() {
		// Stop here if RSS export is turned off
		if (empty(module('forum')->SETTINGS["RSS_EXPORT"])) {
			return module('forum')->_show_error("RSS export is turned off!");
		}
		$forum_id = intval($_GET["id"]);
		// Check if such forum exists
		if (!isset(module('forum')->_forums_array[$forum_id])) {
			return false;
		}
		// Get latest posts
		$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `forum`=".intval($forum_id)." AND `status`='a' ORDER BY `created` DESC LIMIT ".intval(!empty(module('forum')->SETTINGS["RSS_LATEST_IN_FORUM"]) ? module('forum')->SETTINGS["RSS_LATEST_IN_FORUM"] : 15));
		while ($A = db()->fetch_assoc($Q)) {
			$data[] = array(
				"title"			=> _prepare_html($A["subject"], 0),
				"link"			=> process_url("./?object=".FORUM_CLASS_NAME."&action=view_post&id=".$A["id"]),
				"description"	=> _prepare_html(module('forum')->_cut_bb_codes($A["text"]), 0),
				"date"			=> $A["created"],
				"author"		=> _prepare_html($A["user_name"], 0),
				"source"		=> "",
			);
		}
		$forum_info = &module('forum')->_forums_array[$forum_id];
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_forum_".$forum_id,
			"feed_title"		=> "Latest posts inside forum: \""._prepare_html($forum_info["name"], 0)."\"",
			"feed_desc"			=> _prepare_html($forum_info["desc"]),
			"feed_url"			=> process_url(module('forum')->_link_to_forum($forum_id)),
		);
		return common()->rss_page($data, $params);
	}
	
	/**
	* Display latest posts inside topic as RSS feed
	*/
	function _display_for_topic() {
		// Stop here if RSS export is turned off
		if (empty(module('forum')->SETTINGS["RSS_EXPORT"])) {
			return module('forum')->_show_error("RSS export is turned off!");
		}
		$topic_id = intval($_GET["id"]);
		// Check if such topic exists
		$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($topic_id)." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
		// Check topic existance
		if (empty($topic_info["id"])) {
			return module('forum')->_show_error("No such topic!");
		}
		// Get latest posts
		$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `topic`=".intval($forum_id)." AND `status`='a' ORDER BY `created` DESC LIMIT ".intval(!empty(module('forum')->SETTINGS["RSS_LATEST_IN_TOPIC"]) ? module('forum')->SETTINGS["RSS_LATEST_IN_TOPIC"] : 15));
		while ($A = db()->fetch_assoc($Q)) {
			$data[] = array(
				"title"			=> _prepare_html($A["subject"], 0),
				"link"			=> process_url("./?object=".FORUM_CLASS_NAME."&action=view_post&id=".$A["id"]),
				"description"	=> _prepare_html(module('forum')->_cut_bb_codes($A["text"]), 0),
				"date"			=> $A["created"],
				"author"		=> _prepare_html($A["user_name"], 0),
				"source"		=> "",
			);
		}
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_topic_".$topic_info["id"],
			"feed_title"		=> "Latest posts inside topic: \""._prepare_html($topic_info["name"], 0)."\"",
			"feed_desc"			=> _prepare_html($topic_info["desc"], 0),
			"feed_url"			=> process_url("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".intval($topic_info["id"])),
		);
		return common()->rss_page($data, $params);
	}
}
