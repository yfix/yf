<?php

/**
* Board low version methods here
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_low {

	/** @var int */
	var $_topics_per_page	= 150;
	/** @var int */
	var $_posts_per_page	= 50;

	/**
	* Framework constructor
	*/
	function _init () {
		main()->NO_GRAPHICS = true;
	}
	
	/**
	* Show Main
	*/
	function _show_main() {
		// Get type of display
		$TYPE	= "main";
		$_type	= $_GET["id"]{0};
		if (strlen($_GET["id"]) && in_array($_type, array("f","t"))) {
			if ($_type == "f") {
				$TYPE	= "forum";
			} elseif ($_type == "t") {
				$TYPE	= "topic";
			}
		}
		$ID = intval(substr($_GET["id"], 1));

// TODO: global forum navigation and templating
// TODO: access level checks

		// Reference to the categories array
		$cats_array		= &module('forum')->_forum_cats_array;
		// Reference to the forums array
		$forums_array	= &module('forum')->_forums_array;


		// Default low page
		$body = "";
		if ($TYPE == "main" || empty($ID)) {
			$body = $this->_show_home($ID);
		} elseif ($TYPE == "forum")	{
			$body = $this->_show_forum($ID);
		} elseif ($TYPE == "topic")	{
			$body = $this->_show_topic($ID);
		}
		$RW = main()->init_class("rewrite");
		// Replace relative links to their full paths
		if (is_object($RW)) {
			$body = $RW->_rewrite_replace_links($body);
		}
		if (DEBUG_MODE) {
			$body .= "<hr class='clearfloat'>DEBUG INFO:\r\n";
			$body .= common()->_show_execution_time();
//			$body .= common()->show_debug_info();
		}
		$body = str_replace(array("{body}","{css_path}"), array($body, WEB_PATH.tpl()->TPL_PATH), file_get_contents(INCLUDE_PATH.tpl()->TPL_PATH."forum/low/main.stpl"));
		echo $body;
	}
	
	/**
	* Show Home
	*/
	function _show_home($cat_id = 0) {
		$body = "<a href='".url("./?object=forum&action=show".($cat_id ? "&id=".$cat_id : ""))."'><b>".t("Full version")."</b></a><br />\r\n";
		// Reference to the categories array
		$cats_array		= &module('forum')->_forum_cats_array;
		// Reference to the forums array
		$forums_array	= &module('forum')->_forums_array;
		$body .= "<ul>\r\n";
		// Process categories
		foreach ((array)$cats_array as $cat_info) {
			$body .= "<li><a href='".url("./?object=forum&action=low&id=".$cat_info["id"])."'>"._prepare_html($cat_info["name"])."</a></li>\r\n";
			$body .= "<ul>\r\n";
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
				// Skip sub-forums here
				if (!empty($_forum_info["parent"])) {
					continue;
				}
				$body .= "<li>&nbsp;&nbsp;<a href='".url("./?object=forum&action=low&id=f".$_forum_info["id"])."'>"._prepare_html($_forum_info["name"])."</a> <small>(".$_forum_info["num_posts"]." ".t("posts").")</small></li>\r\n";
			}
			$body .= "</ul>\r\n";
		}
		$body .= "</ul>\r\n";
		return $body;
	}
	
	/**
	* Show Forum
	*/
	function _show_forum($forum_id = 0) {
		if (empty($forum_id) || empty(module('forum')->_forums_array[$forum_id])) {
			return "";
		}
		$body = "<a href='".module('forum')->_link_to_forum($forum_id)."'><b>".t("Full version")."</b></a><br />\r\n";
		// Prepare SQL query
		$sql = "SELECT * FROM `".db('forum_topics')."` WHERE `forum`=".intval($forum_id)." ";
		$order_by_sql = " ORDER BY `last_post_date` DESC ";
		$path = "./?object=forum&action=low&id=f".$forum_id;
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, "", $this->_topics_per_page);
		if (!empty($pages)) {
			$body .= "<br /><small>".t("Pages").": ".$pages."</small><br />\r\n";
		}
		$body .= "<ol>\r\n";
		// Process posts
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($topic_info = db()->fetch_assoc($Q)) {
			$body .= "<li><a href='".url("./?object=forum&action=low&id=t".$topic_info["id"])."'>"._prepare_html($topic_info["name"])."</a> <small>(".$topic_info["num_posts"]." ".t("replies").")</small></li>\r\n";
		}
		$body .= "</ol>\r\n";
		return $body;
	}
	
	/**
	* Show Topic
	*/
	function _show_topic($topic_id = 0) {
		if (empty($topic_id)) {
			return "";
		}
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($topic_id)." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
		if (empty($topic_info)) {
			return "";
		}
		$body = "<a href='".url("./?object=forum&action=view_topic&id=".$topic_id)."'><b>".t("Full version")."</b></a><br />\r\n";
		// Prepare SQL query
		$sql = "SELECT * FROM `".db('forum_posts')."` WHERE `topic`=".$topic_id;
		$order_by = " ORDER BY `created` ASC ";
		$path = "./?object=forum&action=low&id=t".$topic_id;
		list($add_sql, $topic_pages, $topic_num_posts) = common()->divide_pages($sql, $path, null, $this->_posts_per_page);
		if (!empty($pages)) {
			$body .= "<br /><small>".t("Pages").": ".$pages."</small><br />\r\n";
		}
		$body .= "<ul>\r\n";
		// Init bb codes module
		$BB_OBJ = main()->init_class("bb_codes", "classes/");
		// Process posts
		$Q = db()->query($sql. $order_by. $add_sql);
		while ($post_info = db()->fetch_assoc($Q)) {
			$body .= "<li>".t("Author").": "._prepare_html($post_info["user_name"]).", Time: "._format_date($post_info["created"], "long")."<br /><br />".$BB_OBJ->_process_text($post_info["text"])."<br /><br /></li>\r\n";
		}
		$body .= "</ul>\r\n";
		return $body;
	}
}
