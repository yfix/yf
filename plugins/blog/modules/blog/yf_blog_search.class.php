<?php

/**
* Blog search handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blog_search {

	/**
	* Constructor
	*/
	function yf_blog_search () {
		// Reference to the parent object
		$this->BLOG_OBJ		= module('blog');
		$this->SETTINGS		= &$this->BLOG_OBJ->SETTINGS;
		$this->USER_RIGHTS	= &$this->BLOG_OBJ->USER_RIGHTS;
	}

	/**
	* Show all blog authors list with links to their blogs
	*/
	function _show_all_blogs ($params = array()) {
		if ($this->BLOG_OBJ->HIDE_TOTAL_ID) {
// TODO
		}
		// Get unique blog posters
		$filter_sql = $this->BLOG_OBJ->USE_FILTER ? $this->BLOG_OBJ->_create_filter_sql() : "";
		if (empty($filter_sql)) {
			$filter_sql = " ORDER BY num_posts DESC ";
		}
		// Give handling to the specific method if needed
		if ($this->BLOG_OBJ->_SEARCH_AS_POSTS) {
			return $this->_search_as_posts($filter_sql);
		}
		$sql = "SELECT * FROM ".db('blog_settings')." AS s WHERE num_posts > 0 ".$filter_sql;
		$path = "./?object=".'blog'."&action=show_all_blogs&id=all";
		$per_page = !empty($params["per_page"]) ? $params["per_page"] : $this->BLOG_OBJ->VIEW_ALL_ON_PAGE;
		list($add_sql, $pages, $total) = common()->divide_pages(str_replace("SELECT *", "SELECT user_id", $sql), $path, null, $per_page);
		// Get contents from db
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) $blogs_infos[$A["user_id"]] = $A;
		// Get their info and sort by user name
		if (!empty($blogs_infos)) {
			$blog_users_infos = user(array_keys($blogs_infos));
		}
		// Process blogs
		foreach ((array)$blogs_infos as $user_id => $cur_blog_info) {
			$user_info	= $blog_users_infos[$user_id];
			$user_name	= _display_name($blog_users_infos[$user_id]);
			$blog_title	= !empty($cur_blog_info["blog_title"]) ? $cur_blog_info["blog_title"] : "";
			// Check privacy permissions
			$view_allowed = $this->BLOG_OBJ->_privacy_check($cur_blog_info["privacy"], 0, $user_id);
			// Force avatar link
			$_force_avatar_link = process_url("./?object=blog&action=show_posts&id=".$user_id);
			// Process template
			$replace2 = array(
				"user_name"			=> _prepare_html($user_name),
				"avatar"			=> _show_avatar($user_id, $user_info, 1, 0, 0, $_force_avatar_link),
				"user_profile_link"	=> _profile_link($user_info),
				"blog_title"		=> _prepare_html($blog_title),
				"blog_desc"			=> nl2br(_prepare_html($cur_blog_info["blog_desc"])),
				"view_blog_link"	=> $view_allowed ? "./?object=".'blog'."&action=show_posts&id=".$user_id._add_get(array("page")) : "",
				"num_blog_posts"	=> intval($cur_blog_info["num_posts"]),
				"num_blog_views"	=> intval($cur_blog_info["num_views"]),
				"num_blog_comments"	=> intval($cur_blog_info["num_comments"]),
				"comments_disabled"	=> $cur_blog_info["allow_comments"] == 9 ? 1 : 0,
				"view_allowed"		=> (int)($view_allowed),
				"location"			=> _prepare_html(_country_name($user_info["country"]).(!empty($user_info["state"]) ? ", ".$user_info["state"] : "").(!empty($user_info["city"]) ? ", ".$user_info["city"] : "")),
				"user_status"		=> in_array($user_info["group"], array(3,4)) ? _prepare_html($user_info["status"]) : "",
				"rss_posts_button"	=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_single_blog&id=".$user_info["id"], "RSS feed for blog: ".(!empty($cur_blog_info["blog_title"]) ? $cur_blog_info["blog_title"] : $user_name."'s blog")),
			);
			$items .= tpl()->parse('blog'."/all_blogs_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"back_url"	=> "./?object=".'blog'."&action=show"._add_get(array("page")),
			"filter"	=> $this->BLOG_OBJ->_show_filter(),
			"no_title"	=> intval((bool)$params["no_title"]),
		);
		return tpl()->parse('blog'."/all_blogs_main", $replace);
	}

	/**
	* Search posts for the specified text and display result as single posts not whole blogs
	*/
	function _search_as_posts ($filter_sql = "") {
		if ($this->BLOG_OBJ->HIDE_TOTAL_ID) {
// TODO
		}
		$sql = "SELECT * FROM ".db('blog_posts')." AS p, ".db('blog_settings')." AS s WHERE p.user_id=s.user_id ".$filter_sql;
		$path = "./?object=".'blog'."&action=".$_GET["action"]."&id=all";
		list($add_sql, $pages, $total) = common()->divide_pages(str_replace("SELECT *", "SELECT id", $sql), $path, null, $this->BLOG_OBJ->VIEW_ALL_ON_PAGE);
		// Get contents from db
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$posts_infos[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users info and sort by user name
		if (!empty($users_ids)) {
			$blog_users_infos = user($users_ids);
		}

		// Process blog posts
		foreach ((array)$posts_infos as $post_id => $cur_post_info) {
			$user_id	= $cur_post_info["user_id"];
			$user_info	= $blog_users_infos[$user_id];
			$user_name	= _display_name($user_info);
			$blog_title	= !empty($cur_post_info["blog_title"]) ? $cur_post_info["blog_title"] : t("Blog");
			// Check privacy permissions
			$view_allowed = $this->BLOG_OBJ->_privacy_check($cur_post_info["privacy"], 0, $user_id);
			// Prepare found text
			$found_text = $cur_post_info["text"];
			if (strlen($found_text) > $this->BLOG_OBJ->POST_TEXT_PREVIEW_LENGTH) {
				$found_text = _truncate($found_text, $this->BLOG_OBJ->_SEARCH_AS_POSTS);
			}
			$found_text = _prepare_html($found_text);
			$found_text = nl2br(highlight($found_text, $this->BLOG_OBJ->_SEARCH_AS_POSTS));
			// Force avatar link
			$_force_avatar_link = process_url("./?object=blog&action=show_posts&id=".$cur_post_info["user_id"]);
			// Process template
			$replace2 = array(
				"user_name"			=> _prepare_html($user_name),
				"avatar"			=> _show_avatar($user_id, $user_info, 1, 0, 0, $_force_avatar_link),
				"user_profile_link"	=> _profile_link($user_info),
				"blog_title"		=> _prepare_html($blog_title),
				"blog_desc"			=> nl2br(_prepare_html($cur_post_info["blog_desc"])),
				"view_blog_link"	=> $view_allowed ? "./?object=".'blog'."&action=show_posts&id=".$user_id._add_get(array("page")) : "",
				"view_post_link"	=> $view_allowed ? "./?object=".'blog'."&action=show_single_post&id=".$cur_post_info["id"]._add_get(array("page")) : "",
				"num_blog_posts"	=> intval($cur_post_info["num_posts"]),
				"num_blog_views"	=> intval($cur_post_info["num_views"]),
				"num_blog_comments"	=> intval($cur_post_info["num_comments"]),
				"view_allowed"		=> (int)($view_allowed),
				"location"			=> _prepare_html($user_info["country"].(!empty($user_info["state"]) ? ", ".$user_info["state"] : "").(!empty($user_info["city"]) ? ", ".$user_info["city"] : "")),
				"user_status"		=> in_array($user_info["group"], array(3,4)) ? _prepare_html($user_info["status"]) : "",
				"post_title"		=> _prepare_html($cur_post_info["title"]),
				"found_text"		=> $found_text,
				"rss_posts_button"	=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_single_blog&id=".$user_info["id"], "RSS feed for blog: ".(!empty($cur_post_info["blog_title"]) ? $cur_post_info["blog_title"] : $user_name."'s blog")),
			);
			$items .= tpl()->parse('blog'."/search_as_posts_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"back_url"	=> "./?object=".'blog'."&action=show"._add_get(array("page")),
			"filter"	=> $this->BLOG_OBJ->_show_filter(),
		);
		return tpl()->parse('blog'."/search_as_posts_main", $replace);
	}

	/**
	* Show blog posts in selected category
	*/
	function _show_in_cat () {
		if ($this->BLOG_OBJ->HIDE_TOTAL_ID) {
// TODO
		}
		$_GET["id"] = intval($_GET["id"]);
		if (!isset($this->BLOG_OBJ->_blog_cats[$_GET["id"]])) {
			return _e(t("No such blogs category!"));
		}
		// Get posts in selected category
		$sql = "SELECT 
				id AS post_id,
				user_id,
				add_date AS post_date,
				title AS post_title,
				num_reads,
				SUBSTRING(text FROM 1 FOR ".intval($this->BLOG_OBJ->POST_TEXT_PREVIEW_LENGTH).") AS post_text 
			FROM ".db('blog_posts')." 
			WHERE active=1 
				AND cat_id=".intval($_GET["id"]);
		$order_sql	= " ORDER BY add_date DESC ";
		$path		= "./?object=".'blog'."&action=".$_GET["action"]."&id=".$_GET["id"];
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->BLOG_OBJ->SHOW_IN_CAT_ON_PAGE);
		// Get contents from db
		$Q = db()->query($sql. $order_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$blog_posts[$A["post_id"]]	= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get users
		if (!empty($users_ids)) {
			$this->_users_infos = user($users_ids, array("id","name","login","nick","name","email","profile_url","photo_verified"));
			$this->_users_blog_settings = $this->BLOG_OBJ->_get_blog_settings_for_user_ids($users_ids);
		}
		// Get comments
		if (!empty($blog_posts)) {
			$this->_num_comments = $this->BLOG_OBJ->_get_num_comments(implode(",",array_keys($blog_posts)));
		}
		// Process posts
		$items .= $this->_process_post_items_in_cat($blog_posts);
		// Process template
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"cat_name"	=> _prepare_html($this->BLOG_OBJ->_blog_cats[$_GET["id"]]),
			"back_url"	=> "./?object=".'blog'."&action=show"._add_get(array("page")),
			"rss_cat_button"=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_cat&id=".$_GET["id"], "RSS feed for posts inside blog category: ".$this->BLOG_OBJ->_blog_cats[$_GET["id"]]),
		);
		return tpl()->parse('blog'."/in_cat_main", $replace);
	}
	
	/**
	* Display post item for the "_show_in_cat"
	*/
	function _process_post_items_in_cat($info_array = array()) {
		foreach ((array)$info_array as $A) {
			// Check privacy permissions
			if (!$this->BLOG_OBJ->_privacy_check($this->_users_blog_settings[$A["user_id"]]["privacy"], $A["privacy"], $A["user_id"])) {
				continue;
			}
			$post_text = $A["post_text"];
			if (strlen($post_text) > $this->BLOG_OBJ->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = substr($A["post_text"], 0, $this->BLOG_OBJ->POST_TEXT_PREVIEW_LENGTH);
			}
			$cur_allow_comments = $A["allow_comments"] > $this->_users_blog_settings[$A["user_id"]]["allow_comments"] ? $A["allow_comments"] : $this->_users_blog_settings[$A["user_id"]]["allow_comments"];
			// Skip non existed users
			if (!isset($this->_users_infos[$A["user_id"]])) {
				continue;
			}
			// Force avatar link
			$_force_avatar_link = process_url("./?object=blog&action=show_posts&id=".$A["user_id"]);
			// Process template
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"	=> $i,
				"avatar"		=> _show_avatar($A["user_id"], $this->_users_infos[$A["user_id"]], 1, 0, 0, $_force_avatar_link),
				"user_name"		=> _prepare_html(_display_name($this->_users_infos[$A["user_id"]])),
				"user_link"		=> _profile_link($A["user_id"]),
				"post_title"	=> $this->BLOG_OBJ->_format_text($A["post_title"]),
				"post_link"		=> "./?object=".'blog'."&action=show_single_post&id=".$A["post_id"]._add_get(array("page")),
				"post_date"		=> _format_date($A["post_date"]),
				"post_text"		=> strip_tags($this->BLOG_OBJ->_format_text($post_text), "<br>"),
				"num_reads"		=> intval($A["num_reads"]),
				"num_comments"	=> $cur_allow_comments < 9 ? intval($this->_num_comments[$A["post_id"]]) : -1,
				"blog_title"	=> _prepare_html($this->_users_blog_settings[$A["user_id"]]["blog_title"]),
				"blog_desc"		=> nl2br(_prepare_html($this->_users_blog_settings[$A["user_id"]]["blog_desc"])),
				"blog_link"		=> "./?object=".'blog'."&action=show_posts&id=".$A["user_id"]._add_get(array("page")),
				"num_posts"		=> intval($A["num_posts"]),
				"rss_posts_button"	=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_single_blog&id=".$A["user_id"], "RSS feed for blog: ".(!empty($this->_users_blog_settings[$A["user_id"]]["blog_title"]) ? $this->_users_blog_settings[$A["user_id"]]["blog_title"] : _display_name($this->_users_infos[$A["user_id"]])."'s blog")),
			);
			$body .= tpl()->parse('blog'."/main_item", $replace2);
		}
		return $body;
	}

	/**
	* Show friends posts
	*/
	function _show_friends_posts () {
		// Check for member id
		if (empty($_GET["id"])) {
			return _e(t("Missing user id"));
		}
		// Try to get user info
		if (!empty($GLOBALS['user_info'])) {
			$user_info = $GLOBALS['user_info'];
		} else {
			$user_info = user($_GET["id"], "full", array("WHERE" => array("active" => 1)));
		}
		if (empty($user_info)) {
			return _e(t("No such user"));
		}
		// Get friends
		$FRIENDS_OBJ	= main()->init_class("friends");
		$friends_ids	= $FRIENDS_OBJ->_get_user_friends_ids($user_info["id"]);
/*
		// Stop here if no friends found
		if (empty($friends_ids)) {
			return "";
		}
*/
		// Get users infos
		if (!empty($friends_ids)) {
			$users_infos = user($friends_ids, "full", array("WHERE" => array("active" => 1)));
			// Try to get latest friends posts
			$sql = 
				"SELECT 
					id AS post_id,
					user_id,
					title AS post_title,
					add_date AS post_date,
					privacy,
					allow_comments,
					num_reads,
					SUBSTRING(text FROM 1 FOR ".intval($this->BLOG_OBJ->POST_TEXT_PREVIEW_LENGTH).") AS post_text 
				FROM ".db('blog_posts')." 
				WHERE active=1 
					AND user_id IN (".implode(",", $friends_ids).")
					AND privacy NOT IN(9)
				ORDER BY add_date DESC ";
			list($add_sql, $pages, $total, $counter) = common()->divide_pages($sql, null, null, $this->BLOG_OBJ->FRIENDS_POSTS_PER_PAGE);
			$Q = db()->query($sql. $add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$posts_array[$A["post_id"]] = $A;
				$users_ids[$A["user_id"]]	= $A["user_id"];
			}
		}
		// Get users
		if (!empty($users_ids)) {
			$this->_users_infos = user($users_ids, array("id","name","login","nick","name","email","profile_url","photo_verified"));
			$this->_users_blog_settings = $this->BLOG_OBJ->_get_blog_settings_for_user_ids($users_ids);
		}
		// Get comments
		if (!empty($posts_array)) {
			$num_comments = $this->BLOG_OBJ->_get_num_comments(implode(",",array_keys($posts_array)));
		}
		// Process records
		foreach ((array)$posts_array as $post_info) {
			$post_user_info = $users_infos[$post_info["user_id"]];
			// Force avatar link
			$_force_avatar_link = process_url("./?object=blog&action=show_posts&id=".$post_info["user_id"]);
			// Store items array
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"		=> ($counter + $i),
				"user_id"			=> intval($post_user_info["id"]),
				"user_name"			=> _prepare_html(_display_name($post_user_info)),
				"avatar"			=> _show_avatar($post_user_info["id"], $post_user_info, 1, 0, 0, $_force_avatar_link),
				"profile_link"		=> "./?object=user_profile&action=show&id=".intval($post_user_info['id']),
				"post_id"			=> intval($post_info["post_id"]),
				"post_link"			=> "./?object=".'blog'."&action=show_single_post&id=".$post_info["post_id"]._add_get(array("page")),
				"post_title"		=> _prepare_html($post_info["post_title"]),
				"post_text"			=> nl2br(_prepare_html($post_info["post_text"])),
				"post_date"			=> _format_date($post_info["post_date"], "long"),
				"num_reads"			=> intval($post_info["num_reads"]),
				"num_comments"		=> intval($num_comments[$post_info["num_reads"]]),
				"blog_title"		=> _prepare_html($this->_users_blog_settings[$post_info["user_id"]]["blog_title"]),
				"blog_desc"			=> nl2br(_prepare_html($this->_users_blog_settings[$post_info["user_id"]]["blog_desc"])),
				"blog_link"			=> "./?object=".'blog'."&action=show_posts&id=".$post_info["user_id"]._add_get(array("page")),
				"rss_posts_button"	=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_single_blog&id=".$post_info["user_id"], "RSS feed for blog: ".(!empty($this->_users_blog_settings[$post_info["user_id"]]["blog_title"]) ? $this->_users_blog_settings[$post_info["user_id"]]["blog_title"] : _display_name($this->_users_infos[$post_info["user_id"]])."'s blog")),
			);
			$items .= tpl()->parse('blog'."/friends_posts_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
			"user_name"			=> _prepare_html(_display_name($user_info)),
			"profile_link"		=> _profile_link($user_info["id"]),
			"rss_friends_button"=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_friends_posts&id=".$_GET["id"], "RSS feed for friends posts for blog: ".(!empty($this->_users_blog_settings[$_GET["id"]]["blog_title"]) ? $this->_users_blog_settings[$_GET["id"]]["blog_title"] : _display_name($this->_users_infos[$_GET["id"]])."'s blog")),
		);
		return tpl()->parse('blog'."/friends_posts_main", $replace);
	}
}
