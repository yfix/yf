<?php

/**
* Blog statistics page handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blog_stats {

	/**
	* Constructor
	*/
	function _init () {
		$this->SETTINGS		= &module('blog')->SETTINGS;
		$this->USER_RIGHTS	= &module('blog')->USER_RIGHTS;
	}
	
	/**
	* Display total blog stats
	*/
	function _show_stats() {
		// Get latest posts
		$sql = "SELECT 
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
				AND privacy NOT IN(9)";
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$sql .= " HAVING user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		$order_by_sql = " ORDER BY add_date DESC";
		// Prepare pager
		$path = "./?object=".'blog'."&action=show_latest_posts";
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		list($add_sql, $latest_pages, $latest_total) = common()->divide_pages($sql, $path, null, module('blog')->STATS_NUM_LATEST_POSTS * module('blog')->FROM_DB_MULTIPLY);
		// Get from db
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$latest_posts_ids[$A["post_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get posts by categories	
		$sql =
			"SELECT 
				COUNT(id) AS num_posts,
				cat_id 
			FROM ".db('blog_posts')." 
			WHERE active=1 
				AND cat_id NOT IN(0,1) 
				AND privacy NOT IN(9)";
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$sql .= " AND user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		$sql .= " GROUP BY cat_id ORDER BY num_posts DESC";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$num_posts_by_cats[$A["cat_id"]] = $A["num_posts"];
		}
		// Get user's ids
		foreach ((array)$latest_posts_ids as $A)	$users_ids[$A["user_id"]] = $A["user_id"];
		foreach ((array)$blog_comments_ids as $A) 	$users_ids[$A["user_id"]] = $A["user_id"];
		foreach ((array)$most_read_ids as $A) 		$users_ids[$A["user_id"]] = $A["user_id"];
		foreach ((array)$top_posts_user_ids as $A) 	$users_ids[$A["user_id"]] = $A["user_id"];
		unset($users_ids[""]);
		// Get users infos and settings
		if (!empty($users_ids))	{
			$this->_users_infos = user($users_ids, array("id","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => 1)));
			$this->_users_blog_settings = module('blog')->_get_blog_settings_for_user_ids($users_ids);
		}
		// Get number of comments for all processed posts
		$posts_ids = array_merge(
			array_keys((array)$latest_posts_ids),
			array_keys((array)$blog_comments_ids),
			array_keys((array)$most_read_ids)
		);
		if (!empty($posts_ids)) {
			$this->_num_comments = module('blog')->_get_num_comments(implode(",",$posts_ids));
		}
		$OBJ = module('blog')->_load_sub_module("blog_search");
		if (is_object($OBJ)) {
			$_POST["sort_by"]		= "num_posts";
			$_POST["sort_order"]	= "DESC";
			module('blog')->save_filter(1);
			$most_active_bloggers = $OBJ->_show_all_blogs(array(
				"no_title"	=> 1,
				"per_page"	=> module('blog')->STATS_NUM_MOST_ACTIVE,
			));
		}
		$i = 0;
		// Process posts categories
		foreach ((array)module('blog')->_blog_cats as $cat_id => $cat_name) {
			$num_posts = $num_posts_by_cats[$cat_id];
			// Skip empty cats if needed
			if (empty($num_posts) && module('blog')->STATS_HIDE_EMPTY_CATS) {
				continue;
			}
			// Process template
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"	=> $i,
				"cat_link"		=> "./?object=".'blog'."&action=show_in_cat&id=".$cat_id._add_get(array("page")),
				"cat_name"		=> _prepare_html($cat_name),
				"num_posts"		=> intval($num_posts),
				"rss_cat_button"=> module('blog')->_show_rss_link("./?object=".'blog'."&action=rss_for_cat&id=".$cat_id, "RSS feed for posts inside blog category: ".$cat_name),
			);
			$blog_cats_posts .= tpl()->parse('blog'."/main_category_item", $replace2);
		}
		// Process latest posts
		$latest_posts			= $this->_process_stats_item($latest_posts_ids, $counter_start, module('blog')->STATS_NUM_LATEST_POSTS);
		// Process main template
		$replace = array(
			"is_logged_in"			=> intval((bool) module('blog')->USER_ID),
			"show_own_posts_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=show_posts"._add_get(array("page")) : "",
			"change_settings_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=settings"._add_get(array("page")) : "",
			"all_blogs_link"		=> "./?object=".'blog'."&action=show_all_blogs"._add_get(array("page")),
			"latest_posts"			=> $latest_posts,
			"latest_total"			=> intval($latest_total),
			"latest_pages"			=> $latest_pages,
			"most_active_bloggers"	=> $most_active_bloggers,
			"most_commented_posts"	=> $most_commented_posts,
			"most_read_posts"		=> $most_read_posts,
			"most_commented_link"	=> "./?object=".'blog'."&action=show_most_commented"._add_get(array("page")),
			"most_read_link"		=> "./?object=".'blog'."&action=show_most_read"._add_get(array("page")),
			"blog_cats_posts"		=> $blog_cats_posts,
			"rss_latest_button"		=> module('blog')->_show_rss_link("./?object=".'blog'."&action=rss_for_all_blogs", "RSS feed for latest blogs posts"),
		);
		return tpl()->parse('blog'."/main_page", $replace);
	}
	
	/**
	* Display most commented records
	*/
	function _show_most_commented($params = array()) {
		if (!empty($_GET["id"])) {
			$_GET["page"] = intval($_GET["id"]);
			unset($_GET["id"]);
		}
		// Get most commented posts
		$sql = "SELECT 
				c.object_id AS post_id,
				b.user_id,
				b.add_date AS post_date,
				b.privacy,
				b.allow_comments,
				b.title AS post_title, 
				b.num_reads, 
				COUNT(c.id) AS num_comments,
				SUBSTRING(b.text FROM 1 FOR ".intval(module('blog')->POST_TEXT_PREVIEW_LENGTH).") AS post_text 
			FROM ".db('comments')." AS c,
				".db('blog_posts')." AS b 
			WHERE c.object_name='"._es('blog')."' 
				AND b.active=1 
				AND b.id=c.object_id 
				AND b.privacy NOT IN(9)
				AND b.allow_comments NOT IN(9)
			GROUP BY c.object_id 
			";
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$sql .= " HAVING b.user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		$order_by_sql = " ORDER BY num_comments DESC";
		// Prepare pager
		$path = "./?object=".'blog'."&action=".$_GET["action"];
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		list($add_sql, $pages, $total, $counter) = common()->divide_pages($sql, $path, null, module('blog')->STATS_NUM_MOST_COMMENTED * module('blog')->FROM_DB_MULTIPLY);
		// Get from db
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$blog_comments_ids[$A["post_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		unset($users_ids[""]);
		// Get users infos and settings
		if (!empty($users_ids))	{
			$this->_users_infos = user($users_ids, array("id","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => 1)));
			$this->_users_blog_settings = module('blog')->_get_blog_settings_for_user_ids($users_ids);
		}
		$posts_ids = array_keys($blog_comments_ids);
		if (!empty($posts_ids)) {
			$this->_num_comments = module('blog')->_get_num_comments(implode(",",$posts_ids));
		}
		// Process most commented posts
		$most_commented_posts	= $this->_process_stats_item($blog_comments_ids, $counter, module('blog')->STATS_NUM_MOST_COMMENTED, $params);

		// For widgets
		if ($params["for_widgets"]) {
			// Process main template
			$replace = array(
				"most_commented_posts"	=> $most_commented_posts,
			);
			return tpl()->parse('blog'."/widget_most_commented", $replace);
		}

		// Process main template
		$replace = array(
			"is_logged_in"			=> intval((bool) module('blog')->USER_ID),
			"show_own_posts_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=show_posts"._add_get(array("page")) : "",
			"change_settings_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=settings"._add_get(array("page")) : "",
			"all_blogs_link"		=> "./?object=".'blog'."&action=show_all_blogs"._add_get(array("page")),
			"most_commented_posts"	=> $most_commented_posts,
			"pages"					=> $pages,
			"total"					=> intval($total),
		);
		return tpl()->parse('blog'."/most_commented_main", $replace);
	}
	
	/**
	* Display most commented records
	*/
	function _show_most_read() {
		if (!empty($_GET["id"])) {
			$_GET["page"] = intval($_GET["id"]);
			unset($_GET["id"]);
		}
		// Get most read posts
		$sql = "SELECT 
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
			";
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$sql .= " HAVING user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		$order_by_sql = " ORDER BY num_reads DESC";
		// Prepare pager
		$path = "./?object=".'blog'."&action=".$_GET["action"];
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		list($add_sql, $pages, $total, $counter) = common()->divide_pages($sql, $path, null, module('blog')->STATS_NUM_MOST_READ * module('blog')->FROM_DB_MULTIPLY);
		// Get from db
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$most_read_ids[$A["post_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		unset($users_ids[""]);
		// Get users infos and settings
		if (!empty($users_ids))	{
			$this->_users_infos = user($users_ids, array("id","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => 1)));
			$this->_users_blog_settings = module('blog')->_get_blog_settings_for_user_ids($users_ids);
		}
		$posts_ids = array_keys($most_read_ids);
		if (!empty($posts_ids)) {
			$this->_num_comments = module('blog')->_get_num_comments(implode(",",$posts_ids));
		}
		// Process most read posts
		$most_read_posts		= $this->_process_stats_item($most_read_ids, $counter, module('blog')->STATS_NUM_MOST_READ);
		// Process main template
		$replace = array(
			"is_logged_in"			=> intval((bool) module('blog')->USER_ID),
			"show_own_posts_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=show_posts"._add_get(array("page")) : "",
			"change_settings_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=settings"._add_get(array("page")) : "",
			"all_blogs_link"		=> "./?object=".'blog'."&action=show_all_blogs"._add_get(array("page")),
			"most_read_posts"		=> $most_read_posts,
			"pages"					=> $pages,
			"total"					=> intval($total),
		);
		return tpl()->parse('blog'."/most_read_main", $replace);
	}
	
	/**
	* Display latest posts (separately)
	*/
	function _show_latest_posts() {
		if (!empty($_GET["id"])) {
			$_GET["page"] = intval($_GET["id"]);
			unset($_GET["id"]);
		}
		// Get latest posts
		$sql = "SELECT 
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
				AND privacy NOT IN(9)";
		// Geo filter
		if (module('blog')->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$sql .= " HAVING user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		$order_by_sql = " ORDER BY add_date DESC";
		// Prepare pager
		$path = "./?object=".'blog'."&action=show_latest_posts";
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		list($add_sql, $pages, $total, $counter) = common()->divide_pages($sql, $path, null, module('blog')->STATS_NUM_LATEST_POSTS * module('blog')->FROM_DB_MULTIPLY);
		// Get from db
		$Q = db()->query($sql. $order_by_sql. $add_sql);
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
		$posts_ids = array_keys($latest_posts_ids);
		if (!empty($posts_ids)) {
			$this->_num_comments = module('blog')->_get_num_comments(implode(",",$posts_ids));
		}
		// Process latest posts
		$latest_posts	= $this->_process_stats_item($latest_posts_ids, $counter, module('blog')->STATS_NUM_LATEST_POSTS);
		// Process main template
		$replace = array(
			"is_logged_in"			=> intval((bool) module('blog')->USER_ID),
			"show_own_posts_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=show_posts"._add_get(array("page")) : "",
			"change_settings_link"	=> module('blog')->USER_ID ? "./?object=".'blog'."&action=settings"._add_get(array("page")) : "",
			"all_blogs_link"		=> "./?object=".'blog'."&action=show_all_blogs"._add_get(array("page")),
			"latest_posts"			=> $latest_posts,
			"pages"					=> $pages,
			"total"					=> intval($total),
			"rss_latest_button"		=> module('blog')->_show_rss_link("./?object=".'blog'."&action=rss_for_all_blogs", "RSS feed for latest blogs posts"),
		);
		return tpl()->parse('blog'."/latest_posts_main", $replace);
	}
	
	/**
	* Display item for the stats
	*/
	function _process_stats_item($info_array = array(), $counter_start = 0, $limit_posts = 0, $params=array()) {
		$this->_posts_counter = 0;
		// Iterate over posts records
		foreach ((array)$info_array as $A) {
			// Check privacy permissions
			if (!module('blog')->_privacy_check($this->_users_blog_settings[$A["user_id"]]["privacy"], $A["privacy"], $A["user_id"])) {
				continue;
			}
			$post_text = $A["post_text"];
			if (strlen($A["post_text"]) > module('blog')->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = _truncate($A["post_text"], module('blog')->POST_TEXT_PREVIEW_LENGTH, true, true);
			}
			$cur_allow_comments = $A["allow_comments"] > $this->_users_blog_settings[$A["user_id"]]["allow_comments"] ? $A["allow_comments"] : $this->_users_blog_settings[$A["user_id"]]["allow_comments"];
			// Skip non existed users
			if (!isset($this->_users_infos[$A["user_id"]])) {
				continue;
			}
			// Limit number of output posts
			if (!empty($limit_posts) && ++$this->_posts_counter > $limit_posts) {
				break;
			}
			// Force avatar link
			$_force_avatar_link = process_url("./?object=blog&action=show_posts&id=".$A["user_id"]);

			// Process template for widgets
			if ($params["for_widgets"]) {
				$replace = array(
					"post_title"		=> module('blog')->_format_text($A["post_title"]),
					"post_link"			=> "./?object=".'blog'."&action=show_single_post&id=".$A["post_id"]._add_get(array("page")),
				);
				$body .= tpl()->parse('blog'."/widgets_post_item", $replace);
				return $body;
			}

			// Process template
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"		=> ($counter_start + $i),
				"avatar"			=> _show_avatar($A["user_id"], $this->_users_infos[$A["user_id"]], 1, 0, 0, $_force_avatar_link),
				"user_name"			=> _prepare_html(_display_name($this->_users_infos[$A["user_id"]])),
				"user_link"			=> _profile_link($A["user_id"]),
				"post_title"		=> module('blog')->_format_text($A["post_title"]),
				"post_link"			=> "./?object=".'blog'."&action=show_single_post&id=".$A["post_id"]._add_get(array("page")),
				"post_date"			=> _format_date($A["post_date"]),
				"post_text"			=> strip_tags(module('blog')->_format_text($post_text), "<br>"),
				"num_reads"			=> intval($A["num_reads"]),
				"num_comments"		=> $cur_allow_comments < 9 ? intval($this->_num_comments[$A["post_id"]]) : -1,
				"blog_title"		=> _prepare_html($this->_users_blog_settings[$A["user_id"]]["blog_title"]),
				"blog_desc"			=> nl2br(_prepare_html($this->_users_blog_settings[$A["user_id"]]["blog_desc"])),
				"blog_link"			=> "./?object=".'blog'."&action=show_posts&id=".$A["user_id"]._add_get(array("page")),
				"num_posts"			=> intval($A["num_posts"]),
				"rss_posts_button"	=> module('blog')->_show_rss_link("./?object=".'blog'."&action=rss_for_single_blog&id=".$A["user_id"], "RSS feed for blog: ".(!empty($this->_users_blog_settings[$A["user_id"]]["blog_title"]) ? $this->_users_blog_settings[$A["user_id"]]["blog_title"] : _display_name($this->_users_infos[$A["user_id"]])."'s blog")),
			);
			$body .= tpl()->parse('blog'."/main_item", $replace2);
		}
		return $body;
	}
}
