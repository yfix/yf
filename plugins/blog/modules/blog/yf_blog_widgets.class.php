<?php

/**
* Blog widgets container
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blog_widgets {

	/**
	* Framework constructor
	*/
	function _init () {
		// Reference to parent object
		$this->BLOG_OBJ		= module('blog');
	}
	
	/**
	* Blog last post
	*/
	function _widget_last_post ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1);
		}
		return $this->BLOG_OBJ->_for_home_page(1, 100, array("for_widgets" => 1));
	}
	
	/**
	* Blog last posts
	*/
	function _widget_last_posts ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1);
		}
		return $this->BLOG_OBJ->_for_home_page(4, 100, array("for_widgets" => 1));
	}
	
	/**
	* Widget categories
	*/
	function _widget_categories ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0, "object" => "blog");
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
		if ($this->BLOG_OBJ->ALLOW_GEO_FILTERING && GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "") {
			$sql .= " AND user_id IN (SELECT id FROM ".db('user')." WHERE country = '"._es(GEO_LIMIT_COUNTRY)."') ";
		}
		$sql .= " GROUP BY cat_id ORDER BY num_posts DESC";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$num_posts_by_cats[$A["cat_id"]] = $A["num_posts"];
		}
		// Process posts categories
		foreach ((array)$this->BLOG_OBJ->_blog_cats as $cat_id => $cat_name) {
			$num_posts = $num_posts_by_cats[$cat_id];
			// Skip empty cats if needed
			if (empty($num_posts) && $this->BLOG_OBJ->STATS_HIDE_EMPTY_CATS) {
				continue;
			}
			// Process template
			$replace2 = array(
				"result_num"	=> $i,
				"cat_link"		=> "./?object=".'blog'."&action=show_in_cat&id=".$cat_id._add_get(array("page")),
				"cat_name"		=> _prepare_html($cat_name),
				"num_posts"		=> intval($num_posts),
				"rss_cat_button"=> $this->BLOG_OBJ->_show_rss_link("./?object=".'blog'."&action=rss_for_cat&id=".$cat_id, "RSS feed for posts inside blog category: ".$cat_name),
			);
			$blog_cats_posts .= tpl()->parse('blog'."/widget_category_item", $replace2);
		}
		$replace = array(
			"cats" => $blog_cats_posts,
		);
		return $blog_cats_posts ? tpl()->parse('blog'."/widget_cats", $replace) : "";
	}

	/**
	* Cloud of tags for blog
	*/
	function _widget_tags_cloud ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1);
		}
		$OBJ = main()->init_class("tags");
		$items = $OBJ->_tags_cloud("blog");
		if (!$items) {
			return "";
		}
		$replace = array(
			"items" => $items,
		);
		return tpl()->parse('blog'."/widget_cloud", $replace);
	}

	/**
	* Most commented blog entries
	*/
	function _widget_most_commented ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1);
		}
		$OBJ = $this->BLOG_OBJ->_load_sub_module("blog_stats");
		return $OBJ->_show_most_commented(array("for_widgets" => 1));
	}

	/**
	* Blog archive links 
	*/
	function _widget_archive ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0);
		}
		$OBJ = $this->BLOG_OBJ->_load_sub_module("blog_right_block");
		return is_object($OBJ) ? $OBJ->_show(array("for_widgets" => 1)) : "";
	}

	/**
	* Friendly sites 
	*/
	function _widget_friendly_sites ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 0);
		}
		if (!main()->USER_ID) {
			return "";
		}
		$OBJ = $this->BLOG_OBJ->_load_sub_module("blog_right_block");
		$blog_links = $OBJ->_show_blog_links();
		if (!$blog_links) {
			return "";
		}
		$replace = array(
			"blog_links"	=> $blog_links,
		);
		return tpl()->parse('blog'."/widget_friendly_sites", $replace);
	}
}
