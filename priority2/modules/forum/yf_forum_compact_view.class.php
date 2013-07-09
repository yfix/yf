<?php

/**
* Compact views container
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_compact_view {
	
	/**
	* Get topic repliers infos
	*/
	function _topic_repliers() {
		main()->NO_GRAPHICS = true;
		// Check input
		$topic_id = substr($_REQUEST["id"], 3);
		if (empty($topic_id)) {
			return "";
		}
		// Try to get topic info
		if (!empty($topic_id)) {
			$this->_topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".intval($topic_id)." ".(!FORUM_IS_ADMIN ? " AND approved=1 " : "")." LIMIT 1");
			module('forum')->_topic_info = $this->_topic_info;
		}
		// Check if such topic exists
		if (empty($this->_topic_info)) {
			return "";
		}
		// Do get repliers ids
		$Q = db()->query("SELECT COUNT(id) AS num_posts,user_id,user_name FROM ".db('forum_posts')." WHERE topic=".intval($topic_id)." AND id!=".intval($this->_topic_info["first_post_id"])." AND user_id!=0 ".(!FORUM_IS_ADMIN ? " AND status='a' " : "")." GROUP BY user_id ORDER BY num_posts DESC");
		while ($A = db()->fetch_assoc($Q)) {
			$users_names[$A["user_id"]] = $A["user_name"];
			$num_posts_by_users[$A["user_id"]] = $A["num_posts"];
		}
		// Process found users
		foreach ((array)$num_posts_by_users as $user_id => $num_posts) {
			$replace = array(
				"num_posts"			=> intval($num_posts),
				"user_name"			=> _prepare_html($users_names[$user_id]),
				"user_profile_link"	=> process_url(module('forum')->_user_profile_link($user_id)),
			);
			$body .= tpl()->parse(FORUM_CLASS_NAME."/compact_topic_repliers_item", $replace);
		}
		// Throw output
		echo t("Topic posts by users").":<br />\r\n";
		echo $body;
	}
	
	/**
	* Compact post view
	*/
	function _post() {
		main()->NO_GRAPHICS = true;
		// Check input
		$post_id = substr($_REQUEST["id"], 3);
		if (!empty($post_id)) {
			$post_info = db()->query_fetch("SELECT * FROM ".db('forum_posts')." WHERE id=".intval($post_id));
		}
		// Check post existance
		if (empty($post_info)) {
			echo "No such post!";
			return false;
		}
		$topic_id = $post_info["topic"];
		// Get topic info
		if (!empty($topic_id)) {
			$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".intval($topic_id)." ".(!FORUM_IS_ADMIN ? " AND approved=1 " : "")." LIMIT 1");
		}
		// Check topic existance
		if (empty($topic_info["id"])) {
			echo "No such topic!";
			return false;
		}
		// Check access rights
		if (!module('forum')->USER_RIGHTS["view_other_topics"] && FORUM_USER_ID != $topic_info["user_id"]) {
			echo "You cannot view topics except those ones you have started!";
			return false;
		}
		if (!module('forum')->USER_RIGHTS["view_post_closed"] && $topic_info["status"] == "c") {
			echo "You cannot view closed topics!";
			return false;
		}
		// Cut-off long posts
		$post_info["text"] = _substr($post_info["text"], 0, 1000);
		// Init bb codes module
		$BB_OBJ = main()->init_class("bb_codes", "classes/");
		$body = is_object($BB_OBJ) ? $BB_OBJ->_process_text($post_info["text"], !$post_info["use_emo"]) : nl2br(_prepare_html($post_info["text"]));
		if (DEBUG_MODE) {
			$body .= "<hr class='clearfloat'>DEBUG INFO:\r\n";
			$body .= common()->_show_execution_time();
//			$body .= common()->show_debug_info();
		}
		echo $body;
	}
}
