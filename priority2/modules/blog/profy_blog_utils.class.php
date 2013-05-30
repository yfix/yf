<?php

/**
* Blog utils container
*
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_blog_utils {

	/**
	* Framework constructor
	*/
	function _init () {
		// Reference to parent object
		$this->BLOG_OBJ		= module(BLOG_CLASS_NAME);
	}

	/**
	* Check privacy permissions (allow current user to view or not)
	*/
	function _privacy_check ($blog_privacy = 0, $post_privacy = 0, $post_author_id = 0) {
		// Public blog and posts
		if ($blog_privacy <= 1 && $post_privacy <= 1) {
			return true;
		}
		// This is owner
		if ($post_author_id == $this->BLOG_OBJ->USER_ID) {
			return true;
		}
		// Public section was over, now begin checking for members,
		// so if user is guest - we deny view here
		if (!$this->BLOG_OBJ->USER_ID) {
			return false;
		}
		// Currently user can set more private status for the current 
		// post comparing to blog global settings (we trying to find greatest private value)
		$cur_privacy = $post_privacy > $blog_privacy ? $post_privacy : $blog_privacy;
		// For members
		if ($cur_privacy == 2) {
			return true;
		// Friends (simple, user need only to add poster to his friends list)
		} elseif ($cur_privacy == 3) {
			if ($post_author_id != $this->BLOG_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend = $FRIENDS_OBJ->_is_a_friend($this->BLOG_OBJ->USER_ID, $post_author_id);
					return $is_a_friend;
				}
			}
			return true;
		// My friends (simple, user need to be in poster's friends list)
		} elseif ($cur_privacy == 4) {
			if ($post_author_id != $this->BLOG_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_my_friend = $FRIENDS_OBJ->_is_a_friend($post_author_id, $this->BLOG_OBJ->USER_ID);
					return $is_my_friend;
				}
			}
			return true;
		// Mutual Friends (both users must have each other in friends lists)
		} elseif ($cur_privacy == 5) {
			if ($post_author_id != $this->BLOG_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend_1 = $FRIENDS_OBJ->_is_a_friend($this->BLOG_OBJ->USER_ID, $post_author_id);
					$is_a_friend_2 = $FRIENDS_OBJ->_is_a_friend($post_author_id, $this->BLOG_OBJ->USER_ID);
					return $is_a_friend_1 && $is_a_friend_2;
				}
			}
			return true;
		// Diary
		} elseif ($cur_privacy == 9 && $post_author_id == $this->BLOG_OBJ->USER_ID) {
			return true;
		}
		// In all other cases -> deny view
		return false;
	}

	/**
	* Check allow comments (allow current user to view/post or not)
	*/
	function _comment_allowed_check ($blog_comments = 0, $post_comments = 0, $post_author_id = 0) {
		// Public blog and posts
		if ($blog_comments <= 1 && $post_comments <= 1) {
			return true;
		}
		// Public section was over, now begin checking for members,
		// so if user is guest - we deny view here
		if (!$this->BLOG_OBJ->USER_ID) {
			return false;
		}
		// Currently user can set more private status for the current 
		// post comparing to blog global settings (we trying to find greatest private value)
		$cur_comments = $post_comments > $blog_comments ? $post_comments : $blog_comments;
		// For members
		if ($cur_comments == 2) {
			return true;
		// Friends (simple, user need only to add poster to his friends list)
		} elseif ($cur_comments == 3) {
			if ($post_author_id != $this->BLOG_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend = $FRIENDS_OBJ->_is_a_friend($this->BLOG_OBJ->USER_ID, $post_author_id);
					return $is_a_friend;
				}
			}
			return true;
		// My friends (simple, user need to be in poster's friends list)
		} elseif ($cur_comments == 4) {
			if ($post_author_id != $this->BLOG_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_my_friend = $FRIENDS_OBJ->_is_a_friend($post_author_id, $this->BLOG_OBJ->USER_ID);
					return $is_my_friend;
				}
			}
			return true;
		// Mutual Friends (both users must have each other in friends lists)
		} elseif ($cur_comments == 5) {
			if ($post_author_id != $this->BLOG_OBJ->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				if (is_object($FRIENDS_OBJ)) {
					$is_a_friend_1 = $FRIENDS_OBJ->_is_a_friend($this->BLOG_OBJ->USER_ID, $post_author_id);
					$is_a_friend_2 = $FRIENDS_OBJ->_is_a_friend($post_author_id, $this->BLOG_OBJ->USER_ID);
					return $is_a_friend_1 && $is_a_friend_2;
				}
			}
			return true;
		// Disabled (No comments)
		} elseif ($cur_comments == 9) {
			return false;
		}
		// In all other cases -> deny view
		return false;
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($OBJ = false) {
		if (!is_object($OBJ)) {
			return false;
		}

		// Main page		
		$OBJ->_store_item(array(
			"url"	=> "./?object=blog&action=show",
		));

		// Get blog categories from db
		$sql = "SELECT `id` FROM `".db('category_items')."` WHERE `cat_id` IN (SELECT `id` FROM `".db('categories')."` WHERE `name`='blog_cats')";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=blog&action=show_in_cat&id=".$A["id"],
			));
		}

		// All blogs by pages
		$sql = "SELECT COUNT(`id`) AS `num` FROM `".db('blog_posts')."` WHERE `active`='1' GROUP BY `user_id`";
		$A = db()->query_fetch($sql);
		$total_pages = ceil(intval($A["num"]) / intval($this->BLOG_OBJ->POSTS_PER_PAGE));
		// Process pages
		if ($total_pages > 1) {
			for ($i = 1; $i <= $total_pages; $i++) {
				$OBJ->_store_item(array(
					"url"	=> "./?object=blog&action=show_all_blogs&id=all&page=".$i,
				));
			}	
		} else {
			$OBJ->_store_item(array(
				"url"	=> "./?object=blog&action=show_all_blogs&id=all",
			));
		}
		
		// User blogs by pages
		$sql = "SELECT DISTINCT `user_id` FROM `".db('blog_posts')."` WHERE `active`='1' ORDER BY `user_id`";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$sql = "SELECT COUNT(`id`) AS `num` FROM `".db('blog_posts')."` WHERE `active`='1' AND `user_id`='".$A["user_id"]."'";
			$B = db()->query_fetch($sql);
			$total_pages = ceil(intval($B["num"]) / intval($this->BLOG_OBJ->POSTS_PER_PAGE));
			// Process pages
			if ($total_pages > 1) {
				for ($i = 1; $i <= $total_pages; $i++) {
					$OBJ->_store_item(array(
						"url"	=> "./?object=blog&action=show_posts&id=".$A["user_id"]."&page=".$i,
					));
				}	
			} else {
				$OBJ->_store_item(array(
					"url"	=> "./?object=blog&action=show_posts&id=".$A["user_id"],
				));
			}
		}

		// Single posts
		$sql = "SELECT `id`,`id2` FROM `".db('blog_posts')."` WHERE `active`='1'";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=blog&action=show_single_post&id=".$A["id"],
			));
		}

		return true;
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$NAV_BAR_OBJ = &$params["nav_bar_obj"];
		if (!is_object($NAV_BAR_OBJ)) {
			return false;
		}
		// Save old items
		$old_items = $params["items"];
		// Create new items
		$items = array();
		if ($this->BLOG_OBJ->USER_ID) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("My Account", "./?object=account");
		}
		if (in_array($_GET["action"], array("", "show"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Blogs");
		} else {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Blogs", "./?object=blog");
		}
		if (in_array($_GET["action"], array("show_single_post"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("View Blog Post");
		} elseif (in_array($_GET["action"], array("show_posts"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("View Blog Posts");
		} elseif (in_array($_GET["action"], array("show_posts_archive"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("View Posts Archive");
		} elseif (in_array($_GET["action"], array("edit_post"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Edit Post");
		} elseif (in_array($_GET["action"], array("add_post"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Add Post");
		} elseif (in_array($_GET["action"], array("edit_comment"))) {
			$items[]	= $NAV_BAR_OBJ->_nav_item("Edit Comment");
		}
		return $items;
	}
}
