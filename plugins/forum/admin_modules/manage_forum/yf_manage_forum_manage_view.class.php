<?php

/**
* View forum content (categories, forums, topics, posts)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_forum_manage_view {

	// 
	function _show_forum_item ($forum_info = array()) {
		$sub_forums = array();
		foreach ((array)module("forum")->_get_sub_forums_ids($forum_info["id"], 1) as $_sub_id) {
			$_sub_info = module("forum")->_forums_array[$_sub_id];
			$sub_forums[$_sub_id] = array(
				"forum_id"		=> intval($_sub_id),
				"name"			=> $_sub_info["name"],
				"desc"			=> $_sub_info["desc"],
				"num_topics"	=> $_sub_info["num_topics"],
				"num_posts"		=> $_sub_info["num_posts"],
				"num_views"		=> $_sub_info["num_views"],
				"activity"		=> module("forum")->_active_select[$_sub_info["status"]],
				"is_active"		=> $_sub_info["status"] == "a" ? 1 : 0,
				"is_closed"		=> intval($_sub_info["options"] == "2" ? 1 : 0),
				"view_link"		=> "./?object=".$_GET["object"]."&action=view_forum&id=".$_sub_info['id']._add_get(array("id")),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_forum&id=".$_sub_id,
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_forum&id=".$_sub_id,
				"active_link"	=> "./?object=".$_GET["object"]."&action=change_forum_activity&id=".$_sub_id,
			);
		}
		// Prepare list of moderators for the current forum
		foreach ((array)module("forum")->_forum_moderators as $_mod_info) {
			if (!in_array($forum_info["id"], explode(",", $_mod_info["forums_list"]))) {
				continue;
			}
			$mods_array[$_mod_info["member_id"]] = module("forum")->_user_profile_link(array(
				"user_id"	=> $_mod_info["member_id"],
				"user_name"	=> $_mod_info["member_name"],
			));
		}
		$replace = array(
			"forum_id"		=> intval($forum_info['id']),
			"new_msg"		=> module("forum")->_forum_new_msg($forum_info['id']),
			"link"			=> "./?object=".$_GET["object"]."&action=view_forum&id=".$forum_info['id']._add_get(array("id")),
			"name"			=> $forum_info["name"],
			"desc"			=> $forum_info["desc"],
			"td_class"		=> !(module("forum")->_i++ % 2) ? module("forum")->css["show1"] : module("forum")->css["show2"],
			"last_post"		=> module("forum")->last_posts[$forum_info['last_post_id']],
			"num_topics"	=> $forum_info["num_topics"],
			"num_posts"		=> $forum_info["num_posts"],
			"num_views"		=> $forum_info["num_views"],
			"activity"		=> module("forum")->_active_select[$forum_info["status"]],
			"is_active"		=> $forum_info["status"] == "a" ? 1 : 0,
			"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_forum&id=".$forum_info['id'],
			"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_forum&id=".$forum_info['id'],
			"sub_forums"	=> $sub_forums,
			"has_sub_forums"=> empty($sub_forums) ? 0 : 1,
			"moderators"	=> $mods_array ? implode(", ", $mods_array) : "",
			"is_closed"		=> intval($forum_info["options"] == "2" ? 1 : 0),
			"active_link"	=> "./?object=".$_GET["object"]."&action=change_forum_activity&id=".$forum_info['id'],
		);
		return tpl()->parse("manage_forum/admin/forum_item", $replace);
	}

	// Main function
	function _view_forum () {
		$_GET['id'] = intval($_GET['id']);
		$forum_info = db()->query_fetch("SELECT * FROM ".db('forum_forums')." WHERE id=".$_GET['id']." LIMIT 1");
		if (empty($forum_info['id'])) {
			return module("forum")->_show_error("No such forum");
		}
		$forum_name = module("forum")->_forums_array[$forum_info['id']]["name"];
		$cat_name = $forum_info["category"] ? module("forum")->_forum_cats_array[$forum_info["category"]]["name"] : "";
		
		$order_by = " ORDER BY created DESC, num_posts DESC ";
		$sql = "SELECT * FROM ".db('forum_topics')." WHERE forum=".$_GET['id'];
		$path = "./?object=".$_GET["object"]."&action=view_forum&id=".$_GET['id'];
		list($add_sql, $pages, $num_rows) = common()->divide_pages($sql, $path);
		
		$Q = db()->query($sql.$order_by.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			module("forum")->topics[$A["id"]] = $A;
		}
		// Try to find last posts in the current forum topics
		module("forum")->_create_last_posts("topic");
		// Process topics
		foreach ((array)module("forum")->topics as $_topic_info) {
			$topic_pages = "";
			// Try to show topic pages
			if (module("forum")->SHOW_TOPIC_PAGES && ($_topic_info["num_posts"] > module("forum")->posts_on_page)) {
				$sql = "SELECT * FROM ".db('forum_posts')." WHERE topic=".$_topic_info['id']." ORDER BY created DESC ";
				$path = "./?object=".$_GET["object"]."&action=view_topic&id=".$_topic_info['id'];
				list(,$topic_pages,) = common()->divide_pages($sql, $path, "topic_pages");
			}
			$author = module("forum")->_user_profile_link($_topic_info);
			$replace = array(
				"new_msg"		=> module("forum")->_topic_new_msg($_topic_info['id']),
				"link"			=> "./?object=".$_GET["object"]."&action=view_topic&id=".$_topic_info['id']._add_get(array("id")),
				"td_class"		=> !($i++ % 2) ? module("forum")->css["show1"] : module("forum")->css["show2"],
				"topic_name"	=> $_topic_info["name"],
				"topic_pages"	=> $topic_pages,
				"author"		=> $author,
				"num_posts"		=> $_topic_info["num_posts"],
				"num_views"		=> $_topic_info["num_views"],
				"last_post"		=> module("forum")->last_posts[$_topic_info['last_post_id']],
				"activity"		=> module("forum")->_active_select[$_topic_info["status"]],
				"is_active"		=> $_topic_info["status"] == "a" ? 1 : 0,
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_topic&id=".$_topic_info['id'],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_topic&id=".$_topic_info['id'],
				"ban_popup_link"=> module("manage_auto_ban")->_popup_link(array("user_id" => intval($_topic_info["user_id"]))),
				"active_link"	=> "./?object=".$_GET["object"]."&action=change_topic_activity&id=".$_topic_info['id'],
			);
			$topics .= tpl()->parse("manage_forum/admin/topic_item", $replace);
		}
		// Prepare sub forums
		$sub_forums = array();
		module("forum")->_create_last_posts("forum");
		foreach ((array)module("forum")->_get_sub_forums_ids($forum_info["id"], 1) as $_sub_id) {
			$sub_forums_items .= module("forum")->_show_forum_item(module("forum")->_forums_array[$_sub_id]);
		}
		// Prepare parent forums
		$parent_forums = array();
		foreach ((array)module("forum")->_get_parent_forums_ids($forum_info["id"]) as $_parent_id) {
			$parent_forums[$_parent_id] = array(
				"id"	=> $_parent_id,
				"name"	=> _prepare_html(module("forum")->_forums_array[$_parent_id]["name"]),
				"link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".$_parent_id._add_get(array("id")),
			);
		}
		$replace_f = array(
			"cat_link"			=> "./?object=".$_GET["object"]._add_get(array("id")),
			"add_link"			=> "./?object=".$_GET["object"]."&action=new_topic&id=".$_GET['id']._add_get(array("id")),
			"future_topic_link"	=> module("forum")->ALLOW_FUTURE_POSTS ? "./?object=".$_GET["object"]."&action=add_future_topic&id=".$_GET['id']._add_get(array("id")) : "",
			"cat_name"			=> $cat_name,
			"forum_name"		=> $forum_name,
			"td_class"			=> !($i++ % 2) ? module("forum")->css["show1"] : module("forum")->css["show2"],
			"num_posts"			=> $num_posts,
			"pages"				=> $pages,
			"topics"			=> $topics,
			"show_filter"		=> "",
			"activity"			=> module("forum")->_active_select[$forum_info["status"]],
			"is_active"			=> $forum_info["status"] == "a" ? 1 : 0,
			"edit_link"			=> "./?object=".$_GET["object"]."&action=edit_forum&id=".$forum_info['id'],
			"delete_link"		=> "./?object=".$_GET["object"]."&action=delete_forum&id=".$forum_info['id'],
			"parent_forums"		=> !empty($parent_forums) ? $parent_forums : "",
			"sub_forums_items"	=> $sub_forums_items,
			"active_link"		=> "./?object=".$_GET["object"]."&action=change_forum_activity&id=".$forum_info['id'],
		);
		return module("forum")->_show_main_tpl(tpl()->parse("manage_forum/admin/view_forum", $replace_f));
	}

	// Process template
	function _view_topic () {
		$_GET['id'] = intval($_GET['id']);
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET['id']." LIMIT 1");
		if (empty($topic_info['id'])) {
			return module("forum")->_show_error("No such topic");
		}
		// Count user view
		module("forum")->_add_topic_view($topic_info);
		
		$forum_name = $topic_info["forum"] ? module("forum")->_forums_array[$topic_info["forum"]]["name"] : "";
		$cat_name	= module("forum")->_forum_cats_array[module("forum")->_forums_array[$topic_info["forum"]]["category"]]["name"];
		$topic_name = $topic_info["name"];
		
		$order_by = " ORDER BY created ASC ";
		$sql = "SELECT * FROM ".db('forum_posts')." WHERE topic=".$_GET['id'];
		$path = "./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id'];
		list($add_sql, $pages, $num_rows) = common()->divide_pages($sql, $path);
		// Insert all required topics into array
		$Q = db()->query($sql.$order_by.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			module("forum")->posts[$A["id"]] = $A;
		}
		// Count number of rank items
		$num_ranks = count(module("forum")->user_ranks);
		// Get topic users info
		module("forum")->_get_topic_users();
		// Process posts
		foreach ((array)module("forum")->posts as $_post_info) {
			if (module("forum")->HIDE_USERS_INFO) {
				$user_name = $_post_info["user_id"] ? (strlen($_post_info["user_name"]) ? $_post_info["user_name"] : $_post_info["user_id"]) : (strlen($_post_info["user_name"]) ? $_post_info["user_name"] : t("Anonymous"));
			} else {
				$user_name = module("forum")->_user_profile_link($_post_info);
			}
			if (strlen(module("forum")->users[$_post_info["user_id"]]["user_avatar"])) {
				$avatar = _show_avatar($_post_info["user_id"], module("forum")->users[$_post_info["user_id"]], 1);
			} else {
				$avatar = "";
			}
			$user_posts = module("forum")->users[$_post_info["user_id"]]["user_posts"];
			if ($_post_info["user_id"]) {
				$rank = module("forum")->user_ranks[module("forum")->users[$_post_info["user_id"]]["user_rank"]]["title"];
				if (!strlen($rank)) $rank = t("member");
			} else {
				$rank = t("guest");
			}
			$user_from = module("forum")->users[$_post_info["user_id"]]["user_from"];
			$reg_date = strlen(module("forum")->users[$_post_info["user_id"]]["user_regdate"]) ? date(module("forum")->format["date"], module("forum")->users[$_post_info["user_id"]]["user_regdate"]) : "";
			$replace = array(
				"td_class"		=> !($i++ % 2) ? module("forum")->css["show1"] : module("forum")->css["show2"],
				"user_name"		=> $user_name,
				"profile_link"	=> module("forum")->_user_profile_link($_post_info),
				"date"			=> date(module("forum")->format["date"], $_post_info["created"]),
				"time"			=> date(module("forum")->format["time"], $_post_info["created"]),
				"text"			=> module("forum")->BB_OBJ->_process_text($_post_info["text"]),
				"post_id"		=> $_post_info['id'],
				"avatar"		=> $avatar,
				"rank"			=> $rank,
				"user_posts"	=> $user_posts ? t("user_posts").": ".$user_posts : "",
				"location"		=> strlen($user_from) ? t("from").": ".$user_from : "",
				"register_date"	=> $reg_date ? t("register").": ".$reg_date : "",
				"user_sig"		=> module("forum")->BB_OBJ->_process_text(module("forum")->users[$_post_info["user_id"]]["user_sig"]),
				"quote_link"	=> "./?object=".$_GET["object"]."&action=reply&id=".$_GET['id']."&msg_id=".$_post_info['id']._add_get(array("id", "msg_id")),
				"activity"		=> module("forum")->_active_select[$_post_info["status"]],
				"is_active"		=> $_post_info["status"] == "a" ? 1 : 0,
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_post&id=".$_GET['id']."&msg_id=".$_post_info['id']._add_get(array("id", "msg_id")),
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_post&id=".$_GET['id']."&msg_id=".$_post_info['id']._add_get(array("id", "msg_id")),
				"ban_popup_link"=> module("manage_auto_ban")->_popup_link(array("user_id" => intval($_post_info["user_id"]))),
				"active_link"	=> "./?object=".$_GET["object"]."&action=change_post_activity&id=".$_post_info['id'],
			);
			$posts .= tpl()->parse("manage_forum/admin/post_item", $replace);
		}
		// Prepare parent forums
		$parent_forums = array();
		foreach ((array)module("forum")->_get_parent_forums_ids($topic_info['forum']) as $_parent_id) {
			$parent_forums[$_parent_id] = array(
				"id"	=> $_parent_id,
				"name"	=> _prepare_html(module("forum")->_forums_array[$_parent_id]["name"]),
				"link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".$_parent_id._add_get(array("id")),
			);
		}
		$replace_t = array(
			"cat_link"			=> "./?object=".$_GET["object"]._add_get(array("id")),
			"forum_link"		=> "./?object=".$_GET["object"]."&action=view_forum&id=".$topic_info["forum"]._add_get(array("id")),
			"new_topic_link"	=> "./?object=".$_GET["object"]."&action=new_topic&id=".$topic_info["forum"]._add_get(array("id")),
			"add_link"			=> "./?object=".$_GET["object"]."&action=reply&id=".$_GET['id']._add_get(array("id")),
			"future_topic_link"	=> module("forum")->ALLOW_FUTURE_POSTS ? "./?object=".$_GET["object"]."&action=add_future_topic&id=".$topic_info["forum"]._add_get(array("id")) : "",
			"future_post_link"	=> module("forum")->ALLOW_FUTURE_POSTS ? "./?object=".$_GET["object"]."&action=add_future_post&id=".$topic_info["id"]._add_get(array("id")) : "",
			"cat_name"			=> $cat_name,
			"forum_name"		=> $forum_name,
			"topic_name"		=> $topic_name,
			"td_class"			=> !($i++ % 2) ? module("forum")->css["show1"] : module("forum")->css["show2"],
			"pages"				=> $pages,
			"posts"				=> $posts,
			"activity"			=> module("forum")->_active_select[$topic_info["status"]],
			"is_active"			=> $topic_info["status"] == "a" ? 1 : 0,
			"edit_link"			=> "./?object=".$_GET["object"]."&action=edit_topic&id=".$topic_info['id'],
			"delete_link"		=> "./?object=".$_GET["object"]."&action=delete_topic&id=".$topic_info['id'],
			"parent_forums"		=> !empty($parent_forums) ? $parent_forums : "",
			"active_link"		=> "./?object=".$_GET["object"]."&action=change_topic_activity&id=".$topic_info['id'],
		);
		return module("forum")->_show_main_tpl(tpl()->parse("manage_forum/admin/view_topic", $replace_t));
	}

	// New topic creation form
	function _new_topic () {
		$_GET['id'] = intval($_GET['id']);
		$forum_info = db()->query_fetch("SELECT * FROM ".db('forum_forums')." WHERE id=".$_GET['id']." LIMIT 1");
		$parent_forum_id = $forum_info['parent'];
		if (empty($forum_info['id'])) {
			return module("forum")->_show_error("No such forum");
		}
		$forum_name = module("forum")->_forums_array[$forum_info['id']]["name"];
		$cat_name	= $forum_info["category"] ? module("forum")->_forum_cats_array[$forum_info["category"]]["name"] : module("forum")->_forum_cats_array[module("forum")->_forums_array[$forum_info["forum"]]["category"]]["name"];
		// Save data
		if (main()->is_post()) {
			$SPAM_EXISTS = false;//db()->query_num_rows("SELECT id FROM ".db('forum_posts')." WHERE created>".(time() - module("forum")->ANTISPAM_TIME)." AND poster_ip='".common()->get_ip()."' LIMIT 1");
			if (!$SPAM_EXISTS) {
				$sql = "INSERT INTO ".db('forum_topics')." (
						forum,
						name,
						user_id,
						user_name,
						created,
						status
					) VALUES (
						".intval($forum_info['id']).",
						'"._es($_POST["title"])."',
						0,
						'"._es(module("forum")->USER_NAME)."',
						".time().",
						'".(module("forum")->APPROVE ? "n" : "a")."' 
					)\r\n";
				db()->query($sql);
				$new_topic_id = db()->insert_id();
				$sql = "INSERT INTO ".db('forum_posts')." (
						parent,
						forum,
						topic,
						subject,
						text,
						user_id,
						user_name,
						created,
						poster_ip,
						status
					) VALUES (
						".intval($parent).",
						".intval($forum_info['id']).",
						".intval($new_topic_id).",
						'"._es($_POST["title"])."',
						'"._es($_POST["text"])."',
						0,
						'"._es(module("forum")->USER_NAME)."',
						".time().",
						'".common()->get_ip()."',
						'".(module("forum")->APPROVE ? "n" : "a")."' 
					)\r\n";
				db()->query($sql);
				$new_post_id = db()->insert_id();
				// Update forum, topic, topic_watch, user tables
				if (!module("forum")->APPROVE) {
					$sql = "UPDATE ".db('forum_topics')." SET 
								num_posts = num_posts + 1,
								first_post_id = ".intval($new_post_id).",
								last_post_id = ".intval($new_post_id)."
							WHERE id=".$new_topic_id;
					db()->query($sql);
					$sql = "UPDATE ".db('forum_forums')." SET 
								num_posts = num_posts + 1,
								num_topics = num_topics + 1,
								last_post_id = ".intval($new_post_id)."
							WHERE id=".$_GET['id'];
					db()->query($sql);
				}
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("forum_forums");
				cache()->refresh("forum_totals");
				cache()->refresh("forum_home_page_posts");
			}
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=view_forum&id=".$_GET['id']._add_get(array("id")));
		}
		// Prepare parent forums
		$parent_forums = array();
		foreach ((array)module("forum")->_get_parent_forums_ids($forum_info['id']) as $_parent_id) {
			$parent_forums[$_parent_id] = array(
				"id"	=> $_parent_id,
				"name"	=> _prepare_html(module("forum")->_forums_array[$_parent_id]["name"]),
				"link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".$_parent_id._add_get(array("id")),
			);
		}
		$replace = array(
			"action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(array("id")),
			"cat_name"			=> $cat_name,
			"forum_name"		=> $forum_name,
			"cat_link"			=> "./?object=".$_GET["object"]._add_get(array("id")),
			"forum_link"		=> "./?object=".$_GET["object"]."&action=view_forum&id=".$_GET['id']._add_get(array("id")),
			"title"				=> "",
			"user_name"			=> module("forum")->USER_NAME,
			"parent_forums"		=> !empty($parent_forums) ? $parent_forums : "",
		);
		return module("forum")->_show_main_tpl(tpl()->parse("manage_forum/new_topic", $replace));
	}

	// Reply to the existing topic (post message)
	function _reply () {
		$_GET['id'] = intval($_GET['id']);
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET['id']." LIMIT 1");
		$parent_forum_id = module("forum")->_forums_array[$topic_info['forum']]["parent"];
		if (empty($topic_info['id'])) {
			return module("forum")->_show_error();
		}
		$forum_name = module("forum")->_forums_array[$topic_info["forum"]]["name"];
		$topic_name = $topic_info["name"];
		$cat_name	= $topic_info["category"] ? module("forum")->_forum_cats_array[$topic_info["category"]]["name"] : module("forum")->_forum_cats_array[module("forum")->_forums_array[$topic_info["forum"]]["category"]]["name"];
		if ($_GET["msg_id"]) {
			$post_info = db()->query_fetch("SELECT text, user_name FROM ".db('forum_posts')." WHERE id=".intval($_GET["msg_id"]));
			$text = "[quote=\"".$post_info["user_name"]."\"]".$post_info["text"]."[/quote]";
		}
		// Save data
		if (main()->is_post()) {
			$SPAM_EXISTS = false;//db()->query_num_rows("SELECT id FROM ".db('forum_posts')." WHERE created>".(time() - module("forum")->ANTISPAM_TIME)." AND poster_ip='".common()->get_ip()."' LIMIT 1");
			if ($topic_info['id'] && !$SPAM_EXISTS) {
				db()->INSERT("forum_posts", array(
					"parent"	=> intval($parent),
					"forum"		=> intval($topic_info["forum"]),
					"topic"		=> intval($topic_info['id']),
					"subject"	=> _es($_POST["subject"]),
					"text"		=> _es($_POST["text"]),
					"user_id"	=> 0,
					"user_name"	=> _es(module("forum")->USER_NAME),
					"created"	=> time(),
					"poster_ip"	=> common()->get_ip(),
					"status"	=> (module("forum")->APPROVE ? "n" : "a"),
				));
				$new_post_id = db()->insert_id();
				// Update forum, topic, topic_watch, user tables
				if (!module("forum")->APPROVE) {
					$sql = "UPDATE ".db('forum_topics')." SET 
								num_posts = num_posts + 1,
								last_post_id = ".intval($new_post_id)."
							WHERE id=".$_GET['id'];
					db()->query($sql);
					$sql = "UPDATE ".db('forum_forums')." SET 
								num_posts = num_posts + 1,
								last_post_id = ".intval($new_post_id)."
							WHERE id=".$topic_info["forum"];
					db()->query($sql);
				}
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("forum_forums");
				cache()->refresh("forum_totals");
				cache()->refresh("forum_home_page_posts");
			}
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id']._add_get(array("id")));
		}
		// Prepare parent forums
		$parent_forums = array();
		foreach ((array)module("forum")->_get_parent_forums_ids($topic_info['forum']) as $_parent_id) {
			$parent_forums[$_parent_id] = array(
				"id"	=> $_parent_id,
				"name"	=> _prepare_html(module("forum")->_forums_array[$_parent_id]["name"]),
				"link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".$_parent_id._add_get(array("id")),
			);
		}
		$replace = array(
			"action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(array("id")),
			"cat_name"			=> $cat_name,
			"forum_name"		=> $forum_name,
			"topic_name"		=> $topic_name,
			"cat_link"			=> "./?object=".$_GET["object"]._add_get(array("id")),
			"forum_link"		=> "./?object=".$_GET["object"]."&action=view_forum&id=".$topic_info["forum"]._add_get(array("id")),
			"topic_link"		=> "./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id']._add_get(array("id")),
			"subject"			=> "Re:".$topic_info["name"],
			"user_name"			=> module("forum")->USER_NAME,
			"text"				=> stripslashes($text),
			"iframe"			=> "",
			"parent_forums"		=> !empty($parent_forums) ? $parent_forums : "",
		);
		return module("forum")->_show_main_tpl(tpl()->parse("manage_forum/reply", $replace));
	}
}
