<?php

/**
* Blog RSS methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blog_rss {

	/**
	* Constructor
	*/
	function _init () {
		$this->SETTINGS		= &module('blog')->SETTINGS;
		$this->USER_RIGHTS	= &module('blog')->USER_RIGHTS;
	}

	/**
	* Display RSS feed for all blogs
	*
	* @access	public
	* @return	
	*/
	function _display_for_all_blogs() {
		// Stop here if RSS export is turned off
		if (empty(module('blog')->ALLOW_RSS_EXPORT)) {
			return _e("RSS export is disabled!");
		}
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$geo_filter_sql = " HAVING user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		// Get latest posts
		$Q = db()->query(
			"SELECT 
				id AS post_id,
				user_id,
				title AS post_title,
				add_date AS post_date,
				privacy,
				allow_comments,
				num_reads,
				SUBSTRING(text FROM 1 FOR ".intval(module('blog')->POST_TEXT_PREVIEW_LENGTH).") AS post_text 
			FROM ".db('blog_posts')." 
			WHERE active=1 
				AND privacy NOT IN(9)
				".$geo_filter_sql."
			ORDER BY add_date DESC 
			LIMIT ".intval(module('blog')->STATS_NUM_LATEST_POSTS)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$latest_posts_ids[$A["post_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		unset($users_ids[""]);
		// Get users infos and settings
		if (!empty($users_ids))	{
			$this->_users_infos = user($users_ids, array("id","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => 1)));
			$this->_users_blog_settings = module('blog')->_get_blog_settings_for_user_ids($users_ids);
		}
		// Process records
		foreach ((array)$latest_posts_ids as $A) {
			// Check privacy permissions
			if (!module('blog')->_privacy_check($this->_users_blog_settings[$A["user_id"]]["privacy"], $A["privacy"], $A["user_id"])) {
				continue;
			}
			$post_text = $A["post_text"];
			if (strlen($post_text) > module('blog')->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = substr($A["post_text"], 0, module('blog')->POST_TEXT_PREVIEW_LENGTH);
			}
			$cur_allow_comments = $A["allow_comments"] > $this->_users_blog_settings[$A["user_id"]]["allow_comments"] ? $A["allow_comments"] : $this->_users_blog_settings[$A["user_id"]]["allow_comments"];
			// Skip non existed users
			if (!isset($this->_users_infos[$A["user_id"]])) {
				continue;
			}
			// Fill item data
			$data[] = array(
				"title"			=> module('blog')->_format_text($A["post_title"]),
				"link"			=> process_url("./?object=".'blog'."&action=show_posts&id=".$A["user_id"]),
				"description"	=> _prepare_html(strip_tags(module('blog')->_format_text($post_text))),
				"date"			=> $A["post_date"],
				"author"		=> _prepare_html(_display_name($this->_users_infos[$A["user_id"]]), 0),
				"source"		=> "",
			);
		}
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_all_blogs_latest",
			"feed_title"		=> "Latest posts inside board",
			"feed_desc"			=> "Latest posts inside board",
			"feed_url"			=> process_url("./?object=".'blog'),
		);
		return common()->rss_page($data, $params);
	}

	/**
	* Display RSS feed for posts from selected blog
	*
	* @access	public
	* @return	
	*/
	function _display_for_single_blog() {
		// Stop here if RSS export is turned off
		if (empty(module('blog')->ALLOW_RSS_EXPORT)) {
			return _e("RSS export is disabled!");
		}
		// Check user id
		$_GET["id"] = intval($_GET["id"]);
		$user_id = $_GET["id"];
		if (module('blog')->HIDE_TOTAL_ID) {
			$user_id = $GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : main()->USER_ID;
		}
		// Try to get given user info
		if (!empty($user_id)) {
			$user_info = user($user_id);
		}
		if (empty($user_info["id"])) {
			return _e("Wrong user ID!");
		}
		// Get current blog settings
		$this->BLOG_SETTINGS = module('blog')->_get_user_blog_settings($user_info["id"]);
		// Check privacy permissions
		if (!module('blog')->_privacy_check($this->BLOG_SETTINGS["privacy"], 0, $user_id)) {
			return _e("You are not allowed to view this blog");
		}
		// Get latest posts
		$Q = db()->query(
			"SELECT * FROM ".db('blog_posts')." 
			WHERE user_id=".intval($user_info["id"])." 
				AND active=1 
			ORDER BY add_date DESC 
			LIMIT ".intval(module('blog')->STATS_NUM_LATEST_POSTS)
		);
		while ($post_info = db()->fetch_assoc($Q)) {
			$posts_array[$post_info["id"]] = $post_info;
		}
		// Get number of user comments
		if (is_array($posts_array)) {
			$num_comments = module('blog')->_get_num_comments(array(
				"objects_ids" => implode(",", array_keys($posts_array)),
			));
		}
		// Process user posts
		foreach ((array)$posts_array as $A) {
			$post_text = $A["text"];
			if (strlen($post_text) > module('blog')->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = substr($A["text"], 0, module('blog')->POST_TEXT_PREVIEW_LENGTH);
			}
			// Fill item data
			$data[] = array(
				"title"			=> module('blog')->_format_text($A["title"]),
				"link"			=> process_url("./?object=".'blog'."&action=show_single_post&id=". (module('blog')->HIDE_TOTAL_ID ? $A["id2"] : $A["id"])),
				"description"	=> _prepare_html(strip_tags(module('blog')->_format_text($post_text))),
				"date"			=> $A["add_date"],
				"author"		=> _prepare_html(_display_name($user_info), 0),
				"source"		=> "",
			);
		}
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_blog_posts_".$user_id,
			"feed_title"		=> _prepare_html("Latest posts inside blog: ".(!empty($this->BLOG_SETTINGS["blog_title"]) ? $this->BLOG_SETTINGS["blog_title"] : _display_name($user_info)."'s blog")),
			"feed_desc"			=> _prepare_html("Latest posts inside blog: ".(!empty($this->BLOG_SETTINGS["blog_title"]) ? $this->BLOG_SETTINGS["blog_title"] : _display_name($user_info)."'s blog")),
			"feed_url"			=> process_url("./?object=".'blog'."&action=show_posts". (module('blog')->HIDE_TOTAL_ID ? "" : "&id=".$user_id)),
		);
		return common()->rss_page($data, $params);
	}

	/**
	* Display RSS feed for posts from selected category
	*
	* @access	public
	* @return	
	*/
	function _display_for_cat() {
		// Stop here if RSS export is turned off
		if (empty(module('blog')->ALLOW_RSS_EXPORT)) {
			return _e("RSS export is disabled!");
		}
		// Check category id
		$_GET["id"] = intval($_GET["id"]);
		if (!isset(module('blog')->_blog_cats[$_GET["id"]])) {
			return _e("No such blogs category!");
		}
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$geo_filter_sql = " HAVING user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		// Get posts in selected category
		$Q = db()->query(
			"SELECT 
				id AS post_id,
				user_id,
				add_date AS post_date,
				title AS post_title,
				num_reads,
				SUBSTRING(text FROM 1 FOR ".intval(module('blog')->POST_TEXT_PREVIEW_LENGTH).") AS post_text 
			FROM ".db('blog_posts')." 
			WHERE active=1 
				AND cat_id=".intval($_GET["id"])."
				".$geo_filter_sql."
			ORDER BY add_date DESC 
			LIMIT ".intval(module('blog')->STATS_NUM_LATEST_POSTS)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$blog_posts[$A["post_id"]]	= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
		}
		// Get users
		if (!empty($users_ids)) {
			$this->_users_infos = user($users_ids, array("id","name","login","nick","name","email","profile_url","photo_verified"));
			$this->_users_blog_settings = module('blog')->_get_blog_settings_for_user_ids($users_ids);
		}
		// Get comments
		if (!empty($blog_posts)) {
			$this->_num_comments = module('blog')->_get_num_comments(implode(",",array_keys($blog_posts)));
		}
		// Process records
		foreach ((array)$blog_posts as $A) {
			// Check privacy permissions
			if (!module('blog')->_privacy_check($this->_users_blog_settings[$A["user_id"]]["privacy"], $A["privacy"], $A["user_id"])) {
				continue;
			}
			$post_text = $A["post_text"];
			if (strlen($post_text) > module('blog')->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = substr($A["post_text"], 0, module('blog')->POST_TEXT_PREVIEW_LENGTH);
			}
			$cur_allow_comments = $A["allow_comments"] > $this->_users_blog_settings[$A["user_id"]]["allow_comments"] ? $A["allow_comments"] : $this->_users_blog_settings[$A["user_id"]]["allow_comments"];
			// Skip non existed users
			if (!isset($this->_users_infos[$A["user_id"]])) {
				continue;
			}
			// Fill item data
			$data[] = array(
				"title"			=> module('blog')->_format_text($A["post_title"]),
				"link"			=> process_url("./?object=".'blog'."&action=show_posts&id=".$A["user_id"]),
				"description"	=> _prepare_html(strip_tags(module('blog')->_format_text($post_text))),
				"date"			=> $A["post_date"],
				"author"		=> _prepare_html(_display_name($this->_users_infos[$A["user_id"]]), 0),
				"source"		=> "",
			);
		}
		$cat_id		= $_GET["id"];
		$cat_name	= module('blog')->_blog_cats[$cat_id];
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_blog_cat_".$_GET["id"],
			"feed_title"		=> _prepare_html("Latest posts inside blog category: ".$cat_name),
			"feed_desc"			=> _prepare_html("Latest posts inside blog category: ".$cat_name),
			"feed_url"			=> process_url("./?object=".'blog'."&action=show_in_cat&id=".$cat_id),
		);
		return common()->rss_page($data, $params);
	}

	/**
	* Display RSS feed for friends posts from selected user
	*
	* @access	public
	* @return	
	*/
	function _display_for_friends_posts() {
		// Stop here if RSS export is turned off
		if (empty(module('blog')->ALLOW_RSS_EXPORT)) {
			return _e("RSS export is disabled!");
		}
		// Check user id
		$_GET["id"] = intval($_GET["id"]);
		$user_id = $_GET["id"];
		// Try to get given user info
		if (!empty($user_id)) {
			$user_info = user($user_id);
		}
		if (empty($user_info["id"])) {
			return _e("Wrong user ID!");
		}
		// Get current blog settings
		$this->BLOG_SETTINGS = module('blog')->_get_user_blog_settings($user_info["id"]);
		// Check privacy permissions
		if (!module('blog')->_privacy_check($this->BLOG_SETTINGS["privacy"], 0, $user_id)) {
			return _e("You are not allowed to view this blog");
		}
		// Get friends
		$FRIENDS_OBJ	= module("friends");
		$friends_ids	= $FRIENDS_OBJ->_get_user_friends_ids($user_info["id"]);
		// Get users infos
		if (!empty($friends_ids)) {
			$users_infos = user($friends_ids, "full", array("WHERE" => array("active" => 1)));
			// Try to get latest friends posts
			$Q = db()->query(
				"SELECT 
					id AS post_id,
					user_id,
					title AS post_title,
					add_date AS post_date,
					privacy,
					allow_comments,
					num_reads,
					SUBSTRING(text FROM 1 FOR ".intval(module('blog')->POST_TEXT_PREVIEW_LENGTH).") AS post_text 
				FROM ".db('blog_posts')." 
				WHERE active=1 
					AND user_id IN (".implode(",", $friends_ids).")
					AND privacy NOT IN(9)
				ORDER BY add_date DESC 
				LIMIT ".intval(module('blog')->STATS_NUM_LATEST_POSTS)
			);
			while ($A = db()->fetch_assoc($Q)) {
				$posts_array[$A["post_id"]] = $A;
				$users_ids[$A["user_id"]]	= $A["user_id"];
			}
		}
		// Get users
		if (!empty($users_ids)) {
			$this->_users_infos = user($users_ids, array("id","name","login","nick","name","email","profile_url","photo_verified"));
			$this->_users_blog_settings = module('blog')->_get_blog_settings_for_user_ids($users_ids);
		}
		// Get comments
		if (!empty($posts_array)) {
			$num_comments = module('blog')->_get_num_comments(implode(",",array_keys($posts_array)));
		}
		// Process records
		foreach ((array)$posts_array as $post_info) {
			$user_info = $users_infos[$post_info["user_id"]];
			$post_text = $post_info["post_text"];
			if (strlen($post_text) > module('blog')->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = substr($post_info["post_text"], 0, module('blog')->POST_TEXT_PREVIEW_LENGTH);
			}
			// Fill item data
			$data[] = array(
				"title"			=> module('blog')->_format_text($post_info["post_title"]),
				"link"			=> process_url("./?object=".'blog'."&action=show_single_post&id=".$post_info["post_id"]),
				"description"	=> _prepare_html(strip_tags(module('blog')->_format_text($post_text))),
				"date"			=> $post_info["add_date"],
				"author"		=> _prepare_html(_display_name($user_info), 0),
				"source"		=> "",
			);
		}
		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "feed_blog_friends_posts_".$user_id,
			"feed_title"		=> _prepare_html("Latest friends posts for blog: ".(!empty($this->BLOG_SETTINGS["blog_title"]) ? $this->BLOG_SETTINGS["blog_title"] : _display_name($user_info)."'s blog")),
			"feed_desc"			=> _prepare_html("Latest friends posts for blog: ".(!empty($this->BLOG_SETTINGS["blog_title"]) ? $this->BLOG_SETTINGS["blog_title"] : _display_name($user_info)."'s blog")),
			"feed_url"			=> process_url("./?object=".'blog'."&action=friends_posts&id=".$user_id),
		);
		return common()->rss_page($data, $params);
	}
}
