<?php

/**
* Inline administration methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_admin {

	/**
	* Constructor
	*/
	function _init () {
		// Synchronization module
		$this->SYNC_OBJ = main()->init_class("forum_sync", FORUM_MODULES_DIR);
		// Init bb codes module
		$this->BB_OBJ = _class("bb_codes");
		// Apply moderator rights here
		if (FORUM_IS_MODERATOR) {
			module('forum')->_apply_moderator_rights();
		}
	}
	
	/**
	* Show Main
	*/
	function _show_main() {
		// Check rights
		if (!FORUM_IS_ADMIN && !FORUM_IS_MODERATOR) {
			return js_redirect("./?object=forum");
		}
		$_GET["id"] = intval($_GET["id"]);
		// Topics action
		if (!empty($_POST["t_act"])) {
			if (in_array($_POST["t_act"], array("open","close"))) {
				$body = $this->_topic_open_close();
			} elseif (in_array($_POST["t_act"], array("pin","unpin"))) {
				$body = $this->_topic_pin_unpin();
			} elseif (in_array($_POST["t_act"], array("approve","unapprove"))) {
				$body = $this->_topic_approve_unapprove();
			} elseif (in_array($_POST["t_act"], array("move", "do_move"))) {
				$body = $this->_topic_move();
			} elseif (in_array($_POST["t_act"], array("merge"))) {
				$body = $this->_topic_merge();
			} elseif (in_array($_POST["t_act"], array("delete"))) {
				$body = $this->_topic_delete();
			}
		}
		// Posts action
		if (!empty($_POST["p_act"])) {
			if (in_array($_POST["p_act"], array("split"))) {
				$body = $this->_posts_split();
			} elseif (in_array($_POST["p_act"], array("approve","unapprove"))) {
				$body = $this->_posts_approve_unapprove();
			} elseif (in_array($_POST["p_act"], array("move", "do_move"))) {
				$body = $this->_posts_move();
			} elseif (in_array($_POST["p_act"], array("merge"))) {
				$body = $this->_posts_merge();
			} elseif (in_array($_POST["p_act"], array("delete"))) {
				$body = $this->_posts_delete();
			}
		}
		return module('forum')->_show_main_tpl($body);
	}
	
	/**
	* Topic Open Close
	*/
	function _topic_open_close() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$_GET["id"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (($_POST["t_act"] == "open" && !module('forum')->USER_RIGHTS["open_topics"]) 
			|| ($_POST["t_act"] == "close" && !module('forum')->USER_RIGHTS["close_topics"])
			|| (FORUM_IS_MODERATOR && !module('forum')->_moderate_forum_allowed($forum_info["id"]))
		) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		if (!empty($_POST["selected_ids"])) {
			// Selected ids
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// New status
			$new_status = $_POST["t_act"] == "open" ? "a" : "c";
			// Update database
			db()->query("UPDATE ".db('forum_topics')." SET status='".$new_status."' WHERE forum=".intval($forum_info["id"])." AND id IN(".implode(",", $selected_ids).")");
		}
		return js_redirect(module('forum')->_link_to_forum($_GET["id"]));
	}
	
	/**
	* Topic Pin Unpin
	*/
	function _topic_pin_unpin() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$_GET["id"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (($_POST["t_act"] == "pin" && !module('forum')->USER_RIGHTS["pin_topics"]) 
			|| ($_POST["t_act"] == "unpin" && !module('forum')->USER_RIGHTS["unpin_topics"])
			|| (FORUM_IS_MODERATOR && !module('forum')->_moderate_forum_allowed($forum_info["id"]))
		) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		if (!empty($_POST["selected_ids"])) {
			// Selected ids
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// New status
			$new_pinned = $_POST["t_act"] == "pin" ? 1 : 0;
			// Update database
			db()->query("UPDATE ".db('forum_topics')." SET pinned=".$new_pinned." WHERE forum=".intval($forum_info["id"])." AND id IN(".implode(",", $selected_ids).")");
		}
		return js_redirect(module('forum')->_link_to_forum($_GET["id"]));
	}
	
	/**
	* Topic Approve Unapprove
	*/
	function _topic_approve_unapprove() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$_GET["id"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (($_POST["t_act"] == "approve" && !module('forum')->USER_RIGHTS["approve_topics"]) 
			|| ($_POST["t_act"] == "unapprove" && !module('forum')->USER_RIGHTS["unapprove_topics"])
			|| (FORUM_IS_MODERATOR && !module('forum')->_moderate_forum_allowed($forum_info["id"]))
		) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		if (!empty($_POST["selected_ids"])) {
			// Selected ids
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// New status
			$new_approve		= $_POST["t_act"] == "approve" ? 1 : 0;
			$new_posts_status	= $_POST["t_act"] == "approve" ? "a" : "u";
			// Update database
			db()->query("UPDATE ".db('forum_topics')." SET approved=".$new_approve." WHERE forum=".intval($forum_info["id"])." AND id IN(".implode(",", $selected_ids).")");
			db()->query("UPDATE ".db('forum_posts')." SET status='".$new_posts_status."' WHERE topic IN(".implode(",", $selected_ids).")");
			// Update forum record
			if (is_object($this->SYNC_OBJ)) {
				$this->SYNC_OBJ->_update_forum_record($forum_info["id"]);
				$this->SYNC_OBJ->_fix_subforums();
			}
		}
		return js_redirect(module('forum')->_link_to_forum($_GET["id"]));
	}
	
	/**
	* Topic Delete
	*/
	function _topic_delete($SILENT_MODE = false, $_force_topic_id = array()) {
		$FORUM_ID = intval($_GET["id"]);
		if (!empty($_force_topic_id)) {
			$force_topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".intval($_force_topic_id));
		}
		if ($force_topic_info["forum"]) {
			$FORUM_ID = intval($force_topic_info["forum"]);
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$FORUM_ID];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Selected topics
		if (!empty($_POST["selected_ids"])) {
			$selected_ids = explode(",", $_POST["selected_ids"]);
		} elseif (!empty($force_topic_info)) {
			$selected_ids = array($force_topic_info["id"]);
		}
		// Check rights
		if (FORUM_IS_MODERATOR && !empty($selected_ids)) {
			$ACCESS_DENIED = false;
			if (!module('forum')->_moderate_forum_allowed($forum_info["id"])) {
				$ACCESS_DENIED = true;
			}
			// Check if any of selected topics is denied to delete by current user
			if (!$ACCESS_DENIED) {
				$Q = db()->query("SELECT * FROM ".db('forum_topics')." WHERE id IN(".implode(",", $selected_ids).")");
				while ($topic_info = db()->fetch_assoc($Q)) {
					if ((FORUM_USER_ID == $topic_info["user_id"] && !module('forum')->USER_RIGHTS["delete_own_topics"])
						|| (FORUM_USER_ID != $topic_info["user_id"] && !module('forum')->USER_RIGHTS["delete_other_topics"])
					) {
						$ACCESS_DENIED = true;
						break;
					}
				}
			}
			if ($ACCESS_DENIED) {
				return module('forum')->_show_error("You are not allowed to perform this action");
			}
		}
		if (!empty($selected_ids)) {
			// Delete topics
			db()->query("DELETE FROM ".db('forum_topics')." WHERE forum=".intval($forum_info["id"])." AND id IN(".implode(",", $selected_ids).")");
			// Delete posts
			db()->query("DELETE FROM ".db('forum_posts')." WHERE topic IN(".implode(",", $selected_ids).")");
			// Update forum record
			if (is_object($this->SYNC_OBJ)) {
				$this->SYNC_OBJ->_update_forum_record($forum_info["id"]);
				$this->SYNC_OBJ->_fix_subforums();
			}
		}
		return !$SILENT_MODE ? js_redirect(module('forum')->_link_to_forum($_GET["id"])) : "";
	}
	
	/**
	* Topic Move
	*/
	function _topic_move() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Check rights
		if (!module('forum')->USER_RIGHTS["move_topics"]) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$_GET["id"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		if (FORUM_IS_MODERATOR && !module('forum')->_moderate_forum_allowed($forum_info["id"])) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Show form
		if (empty($_POST["new_forum_id"]) && !empty($_POST["selected_ids"])) {
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// Get selected topics names
			$Q = db()->query("SELECT id,name FROM ".db('forum_topics')." WHERE id IN(".implode(",", $selected_ids).")");
			while ($A = db()->fetch_assoc($Q)) $topic_names[$A["id"]] = $A["name"];
			// Create array for the template
			foreach ((array)$selected_ids as $topic_id) {
				$selected_topics[$topic_id] = array(
					"topic_id"		=> $topic_id,
					"topic_name"	=> $topic_names[$topic_id],
				);
			}
			// Show template contents
			$replace = array(
				"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
				"forum_id"			=> intval($_GET["id"]),
				"forum_name"		=> $forum_info["name"],
				"forums_box"		=> $this->_forums_box(),
				"leave_link_box"	=> common()->radio_box("leave_link", array("No","Yes"), 1),
				"selected_topics"	=> $selected_topics,
			);
			return tpl()->parse(FORUM_CLASS_NAME."/admin/move_topic_main", $replace);
		// Process data
		} else {
			$leave_link		= intval($_POST["leave_link"]);
			$old_forum_id	= $_GET["id"];
			$new_forum_id	= intval($_POST["new_forum_id"]);
			// Check if new and old forum are equal
			if ($new_forum_id != $old_forum_id) {
				// Get selected ids
				foreach ((array)$_POST as $k => $v) {
					if (substr($k, 0, 4) == "tid_" && $v == 1) {
						$selected_ids[] = intval(substr($k, 4));
					}
				}
				// Selected topics info
				$Q = db()->query("SELECT * FROM ".db('forum_topics')." WHERE id IN(".implode(",", $selected_ids).")");
				while ($A = db()->fetch_assoc($Q)) $topics_array[$A["id"]] = $A;
				// Create new topics in the new forum
				foreach ((array)$topics_array as $topic_id => $topic_info) {
					db()->INSERT("forum_topics", array(
						"forum"				=> intval($new_forum_id),
						"name"				=> _es($topic_info["name"]),
						"desc"				=> _es($topic_info["desc"]),
						"user_id"			=> intval($topic_info["user_id"]),
						"user_name"			=> _es($topic_info["user_name"]),
						"created"			=> intval($topic_info["created"]),
						"status"			=> _es($topic_info["status"]),
						"num_views"			=> intval($topic_info["num_views"]),
						"num_posts"			=> intval($topic_info["num_posts"]),
						"first_post_id"		=> intval($topic_info["first_post_id"]),
						"last_post_id"		=> intval($topic_info["last_post_id"]),
						"last_poster_id"	=> intval($topic_info["last_poster_id"]),
						"last_poster_name"	=> _es($topic_info["last_poster_name"]),
						"last_post_date"	=> intval($topic_info["last_post_date"]),
						"icon_id"			=> intval($topic_info["icon_id"]),
						"pinned"			=> intval($topic_info["pinned"]),
						"approved"			=> intval($topic_info["approved"]),
					));
					$new_topics[$topic_id] = intval(db()->insert_id());
				}
				if (!empty($new_topics)) {
					// Update posts
					foreach ((array)$new_topics as $old_topic_id => $new_topic_id) {
						db()->query("UPDATE ".db('forum_posts')." SET topic=".intval($new_topic_id)." WHERE topic=".intval($old_topic_id));
					}
					// If need to leave links in the old forum - do that
					if ($leave_link) {
						foreach ((array)$new_topics as $old_topic_id => $new_topic_id) {
							db()->query("UPDATE ".db('forum_topics')." SET moved_to='".intval($new_forum_id).",".intval($new_topic_id)."' WHERE id=".intval($old_topic_id));
						}
					// Else delete old topics
					} else {
						// Delete other topics
						db()->query("DELETE FROM ".db('forum_topics')." WHERE id IN(".implode(",", $selected_ids).")");
					}
					// Update forum record
					if (is_object($this->SYNC_OBJ)) {
						$this->SYNC_OBJ->_update_forum_record($old_forum_id);
						$this->SYNC_OBJ->_update_forum_record($new_forum_id);
						$this->SYNC_OBJ->_fix_subforums();
					}
				}
			}
		}
		return js_redirect(module('forum')->_link_to_forum($_GET["id"]));
	}
	
	/**
	* Topic Merge
	*/
	function _topic_merge() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Check rights
		if (!module('forum')->USER_RIGHTS["split_merge"]) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$_GET["id"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		if (FORUM_IS_MODERATOR && !module('forum')->_moderate_forum_allowed($forum_info["id"])) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Process selected ids
		if (!empty($_POST["selected_ids"])) {
			$selected_ids = explode(",", $_POST["selected_ids"]);
			sort($selected_ids);
			if (count($selected_ids) >= 2) {
				$min_id = intval(array_shift($selected_ids));
			}
			if (!empty($min_id) && is_array($selected_ids)) {
				// Update posts
				db()->query("UPDATE ".db('forum_posts')." SET topic=".intval($min_id)." WHERE topic IN(".implode(",", $selected_ids).")");
				// Delete other topics
				db()->query("DELETE FROM ".db('forum_topics')." WHERE id IN(".implode(",", $selected_ids).")");
				// Update forum record
				if (is_object($this->SYNC_OBJ)) {
					$this->SYNC_OBJ->_update_forum_record($forum_info["id"]);
					$this->SYNC_OBJ->_fix_subforums();
				}
			}
		}
		return js_redirect(module('forum')->_link_to_forum($_GET["id"]));
	}
	
	/**
	* Posts Approve Unapprove
	*/
	function _posts_approve_unapprove() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET["id"]." LIMIT 1");
		if (empty($topic_info)) {
			return module('forum')->_show_error("No such topic!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$topic_info["forum"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (($_POST["p_act"] == "approve" && !module('forum')->USER_RIGHTS["approve_posts"]) 
			|| ($_POST["p_act"] == "unapprove" && !module('forum')->USER_RIGHTS["unapprove_posts"])
			|| (FORUM_IS_MODERATOR && !module('forum')->_moderate_forum_allowed($forum_info["id"]))
		) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Process selected ids
		if (!empty($_POST["selected_ids"])) {
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// New status
			$new_approve = $_POST["p_act"] == "approve" ? "a" : "u";
			// Update database
			db()->query("UPDATE ".db('forum_posts')." SET status='".$new_approve."' WHERE topic=".intval($topic_info["id"])." AND id IN(".implode(",", $selected_ids).")");
			// Update forum and topic
			if (is_object($this->SYNC_OBJ)) {
				$this->SYNC_OBJ->_update_topic_record($topic_info["id"]);
				$this->SYNC_OBJ->_update_forum_record($forum_info["id"]);
				$this->SYNC_OBJ->_fix_subforums();
			}
		}
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$_GET["id"]);
	}
	
	/**
	* Posts Delete
	*/
	function _posts_delete() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET["id"]." LIMIT 1");
		if (empty($topic_info)) {
			return module('forum')->_show_error("No such topic!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$topic_info["forum"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (FORUM_IS_MODERATOR) {
			if (!module('forum')->_moderate_forum_allowed($forum_info["id"])) {
				return module('forum')->_show_error("You are not allowed to perform this action");
			}
		}
		if (!empty($_POST["selected_ids"])) {
			// Selected ids
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// Check if post is the first topic post
			if (in_array($topic_info["first_post_id"], $selected_ids)) {
				return module('forum')->_show_error("You cannot delete first post in the topic!");
			}
			// Delete posts
			db()->query("DELETE FROM ".db('forum_posts')." WHERE topic=".intval($topic_info["id"])." AND id IN(".implode(",", $selected_ids).")");
			// Update forum and topic
			if (is_object($this->SYNC_OBJ)) {
				$this->SYNC_OBJ->_update_topic_record($topic_info["id"]);
				$this->SYNC_OBJ->_update_forum_record($forum_info["id"]);
				$this->SYNC_OBJ->_fix_subforums();
			}
		}
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$_GET["id"]);
	}
	
	/**
	* Posts Move
	*/
	function _posts_move() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Check rights
		if (!module('forum')->USER_RIGHTS["move_posts"]) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET["id"]." LIMIT 1");
		if (empty($topic_info)) {
			return module('forum')->_show_error("No such topic!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$topic_info["forum"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (FORUM_IS_MODERATOR) {
			if (!module('forum')->_moderate_forum_allowed($forum_info["id"])) {
				return module('forum')->_show_error("You are not allowed to perform this action");
			}
		}
		// Show form
		if (empty($_POST["new_forum_id"]) && !empty($_POST["selected_ids"])) {
			// Selected ids
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// Check if post is the first topic post
			if (in_array($topic_info["first_post_id"], $selected_ids)) {
				return module('forum')->_show_error("You cannot move first post in the topic!");
			}
			// Get selected posts
			$Q = db()->query("SELECT * FROM ".db('forum_posts')." WHERE id IN(".implode(",", $selected_ids).")");
			while ($A = db()->fetch_assoc($Q)) $posts_array[$A["id"]] = $A;
			// Create array for the template
			foreach ((array)$selected_ids as $post_id) {
				$selected_posts[$post_id] = array(
					"post_id"		=> $post_id,
					"user_name"		=> _prepare_html($posts_array[$post_id]["user_name"]),
					"post_date"		=> module('forum')->_show_date($posts_array[$post_id]["created"], "post_date"),
					"post_text"		=> $this->BB_OBJ->_process_text($posts_array[$post_id]["text"]),
				);
			}
			// Show template contents
			$replace = array(
				"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
				"forum_id"			=> intval($forum_info["id"]),
				"topic_id"			=> intval($topic_info["id"]),
				"old_forum_name"	=> $forum_info["name"],
				"old_topic_name"	=> $topic_info["name"],
				"posts"				=> $selected_posts,
			);
			return tpl()->parse(FORUM_CLASS_NAME."/admin/move_posts_main", $replace);
		} else {
			$old_topic_id	= $topic_info["id"];
			$old_forum_id	= $forum_info["id"];
			// Try to get ID from topic_url field
			$_POST["topic_url"] = trim($_POST["topic_url"]);
			if (!empty($_POST["topic_url"])) {
				// Topic id as plain number
				if (is_numeric($_POST["topic_url"]) && !empty($_POST["topic_url"])) {
					$new_topic_id = intval($_POST["topic_url"]);
				// Topic id as topic url
				} elseif (preg_match("/^http:\/\/(.+?)[\/=]*?view_topic[\/=]*?(&id=)?([0-9]+).*?$/ims", $_POST["topic_url"], $m)) {
					$new_topic_id = intval($m[3]);
				}
			}
			if (empty($new_topic_id)) {
				return module('forum')->_show_error("Wrong topic ID!");
			}
			// Get new topic info
			$new_topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".intval($new_topic_id)." LIMIT 1");
			if (empty($new_topic_info["id"])) {
				return module('forum')->_show_error("No such topic ID!");
			}
			// Get new forum info
			$new_forum_info	= module('forum')->_forums_array[$new_topic_info["forum"]];
			$new_forum_id = $new_forum_info["id"];
			// Check if new and old topics are equal
			if ($new_topic_id != $old_topic_id) {
				// Get selected ids
				foreach ((array)$_POST as $k => $v) {
					if (substr($k, 0, 5) == "post_" && $v == 1) {
						$selected_ids[] = intval(substr($k, 5));
					}
				}
				if (is_array($selected_ids)) {
					// Check if post is the first topic post
					if (in_array($topic_info["first_post_id"], $selected_ids)) {
						return module('forum')->_show_error("You cannot move first post in the topic!");
					}
					// Get selected posts
					$Q = db()->query("SELECT * FROM ".db('forum_posts')." WHERE id IN(".implode(",", $selected_ids).")");
					while ($A = db()->fetch_assoc($Q)) $posts_array[$A["id"]] = $A;
				}
				// Create new posts in the new topic
				if (is_array($selected_ids)) {
					db()->query("UPDATE ".db('forum_posts')." SET topic=".intval($new_topic_id)." WHERE id IN(".implode(",", $selected_ids).")");
				}
				// Update forums and topics
				if (is_object($this->SYNC_OBJ)) {
					$this->SYNC_OBJ->_update_topic_record($old_topic_id);
					$this->SYNC_OBJ->_update_topic_record($new_topic_id);
					$this->SYNC_OBJ->_update_forum_record($old_forum_id);
					$this->SYNC_OBJ->_update_forum_record($new_forum_id);
					$this->SYNC_OBJ->_fix_subforums();
				}
			}
		}
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$_GET["id"]);
	}
	
	/**
	* Posts Split
	*/
	function _posts_split() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Check rights
		if (!module('forum')->USER_RIGHTS["split_merge"]) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET["id"]." LIMIT 1");
		if (empty($topic_info)) {
			return module('forum')->_show_error("No such topic!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$topic_info["forum"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (FORUM_IS_MODERATOR) {
			if (!module('forum')->_moderate_forum_allowed($forum_info["id"])) {
				return module('forum')->_show_error("You are not allowed to perform this action");
			}
		}
		// Show form
		if (empty($_POST["new_forum_id"]) && !empty($_POST["selected_ids"])) {
			// Selected ids
			$selected_ids = explode(",", $_POST["selected_ids"]);
			// Check if post is the first topic post
			if (in_array($topic_info["first_post_id"], $selected_ids)) {
				return module('forum')->_show_error("You cannot move first post in the topic!");
			}
			// Get selected posts
			$Q = db()->query("SELECT * FROM ".db('forum_posts')." WHERE id IN(".implode(",", $selected_ids).")");
			while ($A = db()->fetch_assoc($Q)) $posts_array[$A["id"]] = $A;
			// Create array for the template
			foreach ((array)$selected_ids as $post_id) {
				$selected_posts[$post_id] = array(
					"post_id"		=> $post_id,
					"user_name"		=> _prepare_html($posts_array[$post_id]["user_name"]),
					"post_date"		=> module('forum')->_show_date($posts_array[$post_id]["created"], "post_date"),
					"post_text"		=> $this->BB_OBJ->_process_text($posts_array[$post_id]["text"]),
				);
			}
			// Show template contents
			$replace = array(
				"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
				"forum_id"			=> intval($forum_info["id"]),
				"topic_id"			=> intval($topic_info["id"]),
				"old_forum_name"	=> $forum_info["name"],
				"old_topic_name"	=> $topic_info["name"],
				"forums_box"		=> $this->_forums_box(),
				"posts"				=> $selected_posts,
			);
			return tpl()->parse(FORUM_CLASS_NAME."/admin/split_topic_main", $replace);
		} else {
			$old_topic_id	= $topic_info["id"];
			$old_forum_id	= $forum_info["id"];
			$new_forum_id	= intval($_POST["new_forum_id"]);
			// Get selected ids
			foreach ((array)$_POST as $k => $v) {
				if (substr($k, 0, 5) == "post_" && $v == 1) {
					$selected_ids[] = intval(substr($k, 5));
				}
			}
			if (is_array($selected_ids)) {
				// Check if post is the first topic post
				if (in_array($topic_info["first_post_id"], $selected_ids)) return module('forum')->_show_error("You cannot move first post in the topic!");
				// Get selected posts
				$Q = db()->query("SELECT * FROM ".db('forum_posts')." WHERE id IN(".implode(",", $selected_ids).")");
				while ($A = db()->fetch_assoc($Q)) $posts_array[$A["id"]] = $A;
				// Set new topic first post id
				$first_post_id = intval($selected_ids[0]);
				// Create new topic
				if (!empty($_POST["new_title"]) && !empty($_POST["new_forum_id"]) && !empty($first_post_id)) {
					db()->INSERT("forum_topics", array(
						"forum"				=> intval($new_forum_id),
						"name"				=> _es($_POST["new_title"]),
						"desc"				=> _es($_POST["new_desc"]),
						"user_id"			=> intval($posts_array[$first_post_id]["user_id"]),
						"user_name"			=> _es($posts_array[$first_post_id]["user_name"]),
						"created"			=> time(),
						"first_post_id"		=> intval($first_post_id),
						"last_post_id"		=> intval($first_post_id),
						"last_poster_id"	=> intval($posts_array[$first_post_id]["user_id"]),
						"last_poster_name"	=> _es($posts_array[$first_post_id]["user_name"]),
						"last_post_date"	=> time(),
						"approved"			=> 1,
					));
					$new_topic_id = intval(db()->insert_id());
				}
			}
			// Verify new topic
			if (empty($new_topic_id)) {
				return module('forum')->_show_error("Wrong topic ID!");
			}
			// Get new topic info
			$new_topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".intval($new_topic_id)." LIMIT 1");
			if (empty($new_topic_info["id"])) {
				return module('forum')->_show_error("No such topic ID!");
			}
			// Create new posts in the new topic
			if (is_array($selected_ids)) {
				db()->query("UPDATE ".db('forum_posts')." SET topic=".intval($new_topic_id)." WHERE id IN(".implode(",", $selected_ids).")");
			}
			// Update forums and topics
			if (is_object($this->SYNC_OBJ)) {
				$this->SYNC_OBJ->_update_topic_record($old_topic_id);
				$this->SYNC_OBJ->_update_topic_record($new_topic_id);
				$this->SYNC_OBJ->_update_forum_record($old_forum_id);
				$this->SYNC_OBJ->_update_forum_record($new_forum_id);
				$this->SYNC_OBJ->_fix_subforums();
			}
		}
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$_GET["id"]);
	}
	
	/**
	* Posts Merge
	*/
	function _posts_merge() {
		if (empty($_GET["id"])) {
			return module('forum')->_show_error("No ID!");
		}
		// Check rights
		if (!module('forum')->USER_RIGHTS["split_merge"]) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET["id"]." LIMIT 1");
		if (empty($topic_info)) {
			return module('forum')->_show_error("No such topic!");
		}
		// Get forum info
		$forum_info = module('forum')->_forums_array[$topic_info["forum"]];
		if (empty($forum_info["id"])) {
			return module('forum')->_show_error("No such forum!");
		}
		// Check rights
		if (FORUM_IS_MODERATOR) {
			if (!module('forum')->_moderate_forum_allowed($forum_info["id"])) {
				return module('forum')->_show_error("You are not allowed to perform this action");
			}
		}
		// Selected ids
		$selected_ids = explode(",", trim($_POST["selected_ids"]));
		// Check if post is the first topic post
		if (in_array($topic_info["first_post_id"], $selected_ids)) {
			return module('forum')->_show_error("You cannot merge first post in the topic!");
		}
		// Get selected posts
		if (!empty($selected_ids)) {
			$Q = db()->query("SELECT * FROM ".db('forum_posts')." WHERE id IN(".implode(",", $selected_ids).")");
			while ($A = db()->fetch_assoc($Q)) $posts_array[$A["id"]] = $A;
		}
		// Show form
		if (empty($_POST["merged_post_id"]) && !empty($_POST["selected_ids"])) {
			// Create arrays for the template
			foreach ((array)$posts_array as $post_info) {
				$text_merged[] = $post_info["text"];
				$authors_array[$post_info["user_name"]]	= _prepare_html($post_info["user_name"]).($post_info["user_id"] ? " (#".$post_info["user_id"].")" : "");
				$dates_array[$post_info["created"]]		= module('forum')->_show_date($post_info["created"], "post_date");
				if (!empty($post_info["edit_time"])) {
					$dates_array[$post_info["edit_time"]]	= module('forum')->_show_date($post_info["edit_time"], "edit_time");
				}
			}
			// Show template contents
			$replace = array(
				"form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
				"dates_box"		=> common()->select_box("new_date", $dates_array, $selected, false, 2, "", false),
				"authors_box"	=> common()->select_box("new_author", $authors_array, $selected, false, 2, "", false),
				"selected_ids"	=> implode(",", $selected_ids),
				"merged_post_id"=> intval(array_pop($selected_ids)),
				"text"			=> implode("\r\n\r\n", $text_merged),
			);
			return tpl()->parse(FORUM_CLASS_NAME."/admin/merge_posts_main", $replace);
		} else {
			$old_topic_id		= $topic_info["id"];
			$old_forum_id		= $forum_info["id"];
			$merged_post_id		= intval($_POST["merged_post_id"]);
			$merged_post_info	= $posts_array[$merged_post_id];
			$new_author_name	= $_POST["new_author"];
			// Try to get user id
			foreach ((array)$posts_array as $post_info) {
				if ($post_info["user_name"] == $new_author_name) {
					$new_user_id = $post_info["user_id"];
					break;
				}
			};
			if (!empty($merged_post_id)) {
				// Update post
				$sql = "UPDATE ".db('forum_posts')." SET 
						text		= '"._es($_POST["new_text"])."', 
						user_name	= '"._es($new_author_name)."', 
						user_id	= ".intval($new_user_id).", 
						created	= ".intval($_POST["new_date"])."
					WHERE id=".intval($merged_post_id);
				db()->query($sql);
			}
			// Delete other posts
			if (is_array($selected_ids)) {
				foreach ((array)$posts_array as $post_id => $post_info) {
					if ($post_info["id"] == $merged_post_id) continue;
					else $ids_to_delete[$post_id] = $post_id;
				}
			}
			if (is_array($ids_to_delete)) {
				db()->query("DELETE FROM ".db('forum_posts')." WHERE id IN(".implode(",", $ids_to_delete).")");
			}
			// Update forums and topics
			if (is_object($this->SYNC_OBJ)) {
				$this->SYNC_OBJ->_update_topic_record($old_topic_id);
				$this->SYNC_OBJ->_update_forum_record($old_forum_id);
				$this->SYNC_OBJ->_fix_subforums();
			}
		}
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$_GET["id"]);
	}

	/**
	* Forums Box
	*/
	function _forums_box($name_in_form = "new_forum_id", $selected = "") {
		// Create forum jump array
		$forum_divider	= "&nbsp;&nbsp;&#0124;-- ";
		$forums_array	= array();
		foreach ((array)module('forum')->_forum_cats_array as $cat_info) {
			foreach ((array)module('forum')->_forums_array as $forum_info) {
				if ($forum_info["category"] != $cat_info["id"]) {
					continue;
				}
				$forums_array[$cat_info["name"]][$forum_info["id"]] = $forum_divider. $forum_info["name"];
			}
		}
		return common()->select_box($name_in_form, $forums_array, $selected, false, 2, "", false);
	}	
}
