<?php

/**
* Show topic contents (tree-style)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_view_topic_tree {

	/**
	* Constructor
	*/
	function _init () {
		// Apply moderator rights here
		if (FORUM_IS_MODERATOR) {
			module('forum')->_apply_moderator_rights();
		}
	}
	
	/**
	* Show Main
	*/
	function _show_main() {
		$_GET["id"]		= intval($_GET["id"]);
		$_GET["page"]	= intval($_GET["page"]);
		if (empty($_GET["page"])) {
			unset($_GET["page"]);
		}
		if (empty(module('forum')->_topic_info) && !empty($_GET["id"])) {
			$this->_topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($_GET["id"])." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
			module('forum')->_topic_info = $this->_topic_info;
		} else {
			$this->_topic_info = &module('forum')->_topic_info;
		}
		// Check topic existance
		if (empty($this->_topic_info["id"])) {
			return module('forum')->_show_error("No such topic!");
		}
		// Check access rights
		if (!module('forum')->USER_RIGHTS["view_other_topics"] && FORUM_USER_ID != $this->_topic_info["user_id"]) {
			return module('forum')->_show_error("You cannot view topics except those ones you have started!");
		}
		if (!module('forum')->USER_RIGHTS["view_post_closed"] && $this->_topic_info["status"] != "a") {
			return module('forum')->_show_error("You cannot view closed topics!");
		}
		// Get current post info
		$post_id = !empty($_GET["page"]) ? intval($_GET["page"]) : $this->_topic_info["first_post_id"];
		if (!empty($post_id)) {
			$this->_post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `id`=".intval($post_id));
		}
		if (empty($this->_post_info["id"])) {
			return module('forum')->_show_error("No such post!");
		}
		// Reference to the forums array
		$this->_forum_info	= &module('forum')->_forums_array[$this->_topic_info["forum"]];
		// Reference to the categories array
		$this->_cat_info	= &module('forum')->_forum_cats_array[$this->_forum_info["category"]];
		// Skip non-active forums and categories
		if ($this->_forum_info["status"] != "a" || $this->_cat_info["status"] != "a") {
			return module('forum')->_show_error("Forum is inactive!");
		}
		// Check user group access rights to the current forum
		$only_for_groups = $this->_forum_info["user_groups"] ? explode(",", $this->_forum_info["user_groups"]) : "";
		if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
			return module('forum')->_show_error("Private Forum!");
		}
		// Count user view
		db()->_add_shutdown_query("UPDATE `".db('forum_topics')."` SET `num_views`=`num_views`+1 WHERE `id`=".intval($this->_topic_info["id"]));
		// Add read topic record
		if (FORUM_USER_ID && module('forum')->SETTINGS["USE_READ_MESSAGES"]) {
			module('forum')->_set_topic_read($this->_topic_info);
		}
		// Get user info
		if (!empty($this->_post_info["user_id"])) {
			$users_array = module('forum')->_get_users_infos(array($this->_post_info["user_id"] => $this->_post_info["user_id"]));
			$this->_user_info = $users_array[$this->_post_info["user_id"]];
		}
		// Set required params
		$forum_is_closed	= $this->_forum_info["options"] == "2" ? 1 : 0;
		$topic_is_closed	= intval($this->_topic_info["status"] != "a");
		$allow_reply		= intval(!$forum_is_closed && !$topic_is_closed);
		$allow_new_topic	= !$forum_is_closed && module('forum')->USER_RIGHTS["post_new_topics"];
		// Additional rights checkin
		if (FORUM_USER_ID && $this->_topic_info["user_id"] == FORUM_USER_ID && module('forum')->USER_RIGHTS["reply_own_topics"]) {
			$allow_reply = 1;
		}
		if (((FORUM_USER_ID && $this->_topic_info["user_id"] != FORUM_USER_ID) || !FORUM_USER_ID) && module('forum')->USER_RIGHTS["reply_other_topics"]) {
			$allow_reply = 1;
		}
		// Deny guests posting (if needed)
		if (!FORUM_USER_ID && !module('forum')->SETTINGS["ALLOW_GUESTS_POSTS"]) {
			$allow_reply		= 0;
			$allow_new_topic	= 0;
		}
		$use_fast_reply		= intval(module('forum')->SETTINGS["USE_FAST_REPLY"] && $allow_reply);
		$use_topic_options	= intval(FORUM_USER_ID && module('forum')->SETTINGS["USE_TOPIC_OPTIONS"] && $allow_reply);
		// Process users reputation
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids(array($this->_user_info["id"]));
			foreach ((array)$users_reput_info as $reput_user_id => $reput_info) {
				module('forum')->_reput_texts[$reput_user_id] = $REPUT_OBJ->_show_for_user($reput_user_id, $users_reput_info[$reput_user_id]);
			}
		}
		// Statistics
		$STATS_OBJ = main()->init_class("forum_stats", FORUM_MODULES_DIR);
		// Post item object
		$POST_ITEM_OBJ = main()->init_class("forum_post_item", FORUM_MODULES_DIR);
		if (is_object($POST_ITEM_OBJ)) {
			$post_info		= &$this->_post_info;
			if (!empty($users_reput_info)) {
				module('forum')->_reput_texts_for_posts[$post_info["id"]] = $REPUT_OBJ->_show_for_user($post_info["user_id"], $users_reput_info[$post_info["user_id"]], false, array("forum_posts", $post_info["id"]));
			}
			$user_info		= $users_array[$post_info["user_id"]];
			$is_first_post	= $this->_topic_info["first_post_id"] != $post_info["id"];
			$current_post	.= $POST_ITEM_OBJ->_show_post_item($post_info, $user_info, null, "/view_topic_flat/post_item", $is_first_post, $allow_reply);
		}
		// Process template
		$replace = array(
			"is_admin"			=> intval(FORUM_IS_ADMIN),
			"is_moderator"		=> intval(FORUM_IS_ADMIN || (FORUM_IS_MODERATOR && module('forum')->_moderate_forum_allowed($this->_forum_info["id"]))),
			"cat_link"			=> "./?object=".FORUM_CLASS_NAME._add_get(array("page")),
			"forum_link"		=> module('forum')->_link_to_forum($this->_topic_info["forum"]),
			"topic_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$this->_topic_info["id"]._add_get(array("page")),
			"new_topic_link"	=> $allow_new_topic ? "./?object=".FORUM_CLASS_NAME."&action=new_topic&id=".$this->_topic_info["forum"]._add_get(array("page")) : "",
			"add_post_link"		=> $allow_reply ? "./?object=".FORUM_CLASS_NAME."&action=new_post&id=".$this->_topic_info["id"]._add_get() : "",
			"track_topic_link"	=> FORUM_USER_ID && module('forum')->SETTINGS["ALLOW_TRACK_TOPIC"] ? "./?object=".FORUM_CLASS_NAME."&action=subscribe_topic&id=".$this->_topic_info["id"]._add_get() : "",
			"email_topic_link"	=> FORUM_USER_ID && module('forum')->SETTINGS["ALLOW_EMAIL_TOPIC"] ? "./?object=".FORUM_CLASS_NAME."&action=email_topic&id=".$this->_topic_info["id"]._add_get() : "",
			"print_topic_link"	=> module('forum')->SETTINGS["ALLOW_PRINT_TOPIC"] ? "./?object=".FORUM_CLASS_NAME."&action=print_topic&id=".$this->_topic_info["id"]._add_get() : "",
			"cat_name"			=> _prepare_html($this->_cat_info["name"]),
			"forum_id"			=> $this->_forum_info["id"],
			"forum_name"		=> _prepare_html($this->_forum_info["name"]),
			"topic_id"			=> $this->_topic_info["id"],
			"topic_name"		=> _prepare_html($this->_topic_info["name"]),
			"topic_pages"		=> $topic_pages,
			"current_post"		=> $current_post,
			"tree_view_link"	=> "./?object=".FORUM_CLASS_NAME."&action=change_topic_view&id=1"._add_get(),
			"flat_view_link"	=> "./?object=".FORUM_CLASS_NAME."&action=change_topic_view&id=2"._add_get(),
			"link_to_post_base"	=> process_url("./?object=".FORUM_CLASS_NAME."&action=view_post&id=0"._add_get(array("page"))),
			"board_fast_nav"	=> module('forum')->SETTINGS["ALLOW_FAST_JUMP_BOX"] ? module('forum')->_board_fast_nav_box() : "",
			"topic_online"		=> is_object($STATS_OBJ) ? $STATS_OBJ->_show_topic_stats() : "",
			"search_form_action"=> module('forum')->USER_RIGHTS["use_search"] && module('forum')->SETTINGS["ALLOW_SEARCH"] ? "./?object=".FORUM_CLASS_NAME."&action=search". _add_get() : "",
			"forum_closed"		=> intval($forum_is_closed),
			"topic_closed"		=> !$forum_is_closed ? $topic_is_closed : "",
			"use_fast_reply"	=> $use_fast_reply,
			"use_topic_options"	=> $use_topic_options,
			"fast_reply_form"	=> $use_fast_reply ? $this->_show_fast_reply_form() : "",
			"topic_options_form"=> $use_topic_options ? $this->_show_topic_options_form() : "",
			"posts_tree"		=> $this->_show_tree_view(),
			"rss_topic_button"	=> module('forum')->_show_rss_link("./?object=".FORUM_CLASS_NAME."&action=rss_forum&id=".$this->_topic_info["forum"], "RSS feed for topic: ".$this->_topic_info["name"]),
			"allow_change_view"	=> intval((bool) module('forum')->SETTINGS["ALLOW_CHANGE_TOPIC_VIEW"]),
		);
		return module('forum')->_show_main_tpl(tpl()->parse(FORUM_CLASS_NAME."/view_topic_tree/main", $replace));
	}
	
	/**
	* Show Fast Reply Form
	*/
	function _show_fast_reply_form() {
		$replace = array(
			"post_form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=save_post&id=".$_GET["id"]. _add_get(),
			"topic_id"			=> intval($this->_topic_info["id"]),
			"forum_id"			=> intval($this->_forum_info["id"]),
			"act_name" 			=> "new_post",
		);
		return tpl()->parse(FORUM_CLASS_NAME."/view_topic_flat/fast_reply", $replace);
	}
	
	/**
	* Show Topic Options Form
	*/
	function _show_topic_options_form() {
		$replace = array(
			"track_topic_link"		=> FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=subscribe_topic&id=".$this->_topic_info["id"]._add_get() : "",
			"subscribe_forum_link"	=> FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=subscribe_forum&id=".$this->_forum_info["id"]._add_get() : "",
		);
		return tpl()->parse(FORUM_CLASS_NAME."/view_topic_flat/topic_options", $replace);
	}
	
	/**
	* Show tree view contents
	*/
	function _show_tree_view() {
		// Get all posts for the topic
		$Q = db()->query("SELECT `id`,`created`,`parent`,`subject`,`user_id`,`user_name` FROM `".db('forum_posts')."` WHERE `topic`=".intval($this->_topic_info["id"])." ORDER BY `created` DESC");
		while ($A = db()->fetch_assoc($Q)) $this->_posts_array[$A["id"]] = $A;
		// First we need to build posts tree
		$this->_posts_tree		= $this->_build_posts_tree();
		// Get spacer template
		$this->_spacer = tpl()->parse(FORUM_CLASS_NAME."/view_topic_tree/spacer");
		// Process template
		$replace = array(
			"root_item"		=> $this->_show_topic_tree_item($this->_topic_info["first_post_id"]),
			"items"			=> $this->_display_posts_tree($this->_posts_tree),
			"pages"			=> $pages,
		);
		return $tree_view = tpl()->parse(FORUM_CLASS_NAME."/view_topic_tree/tree_main", $replace);
	}

	/**
	* Build multidimensional array (storing posts tree)
	*/
	function _build_posts_tree($parent_id = 0, $level = 1) {
		$posts_tree = array();
		// Filter posts to show on the current level
		foreach ((array)$this->_posts_array as $post_info) {
			// Skip first post in topic
			if ($post_info["id"] == $this->_topic_info["first_post_id"]) {
				continue;
			}
			// Skip posts with parent for the first level
			if ($level == 1 && !empty($post_info["parent"])) {
				continue;
			}
			// Skip all not children for the current parent
			if (!empty($parent_id) && $post_info["parent"] != $parent_id) {
				continue;
			}
			// Create new filtered array
			$posts_to_show[$post_info["id"]] = $post_info;
		}
		// Process filtered posts
		foreach ((array)$posts_to_show as $post_info) {
			// Fill tree
			$posts_tree[$post_info["id"]] = array(
				"level"			=> $level,
				"post_id"		=> $post_info["id"],
				"children"		=> $this->_build_posts_tree($post_info["id"], $level + 1),
			);
		}
		return $posts_tree;
	}

	/**
	* Display Posts Tree
	*/
	function _display_posts_tree($posts_tree = array()) {
		// Process filtered posts
		foreach ((array)$posts_tree as $tree_info) {
			// Show current level post
			$body .= $this->_show_topic_tree_item($tree_info["post_id"], $tree_info["level"], $tree_info["is_last_item"]);
			// Try to find children posts
			if (!empty($tree_info["children"])) {
				$body .= $this->_display_posts_tree($tree_info["children"]);
			}
		}
		return $body;
	}
	
	/**
	* Show Topic Tree Item
	*/
	function _show_topic_tree_item($post_id = 0, $level = 0) {
		$post_info = &$this->_posts_array[$post_id];
		if (empty($post_info)) {
			return false;
		}
		$replace = array(
			"add_image"			=> /*$level > 0 ? (module('forum')->TOPIC_TREE_IMAGES[$is_last_item ? 1 : 2]) : */"",
			"user_profile_link"	=> $post_info["user_id"] ? module('forum')->_user_profile_link($post_info["user_id"]) : "",
			"user_id"			=> intval($post_info["user_id"]),
			"user_name"			=> _prepare_html($post_info["user_name"]),
			"post_title"		=> $level != 0 || empty($post_info["subject"]) ? "Re: "._prepare_html($this->_topic_info["name"]) : _prepare_html($post_info["subject"]),
			"post_date"			=> module('forum')->_show_date($post_info["created"], "post_date"),
			"post_id"			=> intval($post_info["id"]),
			"is_current_post"	=> intval($post_info["id"] == $this->_post_info["id"]),
			"view_topic_link"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$this->_topic_info["id"]."&post_id=".intval($post_info["id"]),
			"spacer"			=> $this->_display_spacer($post_id, $level),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/view_topic_tree/tree_item", $replace);
	}
	
	/**
	* Display Spacer
	*/
	function _display_spacer($post_id = 0, $level = 0) {
		if ($level == 0) {
			return false;
		}
		// Build result string
		for ($i = 0; $i < $level; $i++) {
			$body .= $this->_spacer;
		}
		return $body;
	}

	/**
	* Post Act Box
	*/
	function _p_act_box ($name = "p_act") {
		if (module('forum')->USER_RIGHTS["split_merge"])			$p_actions["merge"]		= t('Merge Posts');
		if (module('forum')->USER_RIGHTS["move_posts"])			$p_actions["move"]		= t('Move Posts');
		if (module('forum')->USER_RIGHTS["delete_other_posts"])	$p_actions["delete"]	= t('Delete Posts');
		if (module('forum')->USER_RIGHTS["split_merge"])			$p_actions["split"]		= t('Split Topic');
		if (module('forum')->USER_RIGHTS["approve_posts"])		$p_actions["approve"]	= t('Set Visible')." (".t('Approve Post').")";
		if (module('forum')->USER_RIGHTS["unapprove_posts"])		$p_actions["unapprove"]	= t('Set Invisible')." (".t('Unapprove Post').")";
		return !empty($p_actions) ? common()->select_box($name, $p_actions, "", 0, 2, "", false) : "";
	}
}
