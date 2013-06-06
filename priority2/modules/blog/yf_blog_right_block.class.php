<?php

/**
* Blog comments handler
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_blog_right_block {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->BLOG_OBJ		= module(BLOG_CLASS_NAME);
		$this->SETTINGS		= &$this->BLOG_OBJ->SETTINGS;
		$this->USER_RIGHTS	= &$this->BLOG_OBJ->USER_RIGHTS;
	}

	/**
	* Right block contents
	*/
	function _show ($params = array()) {
		$user_info = &$GLOBALS['user_info'];
		if (empty($user_info)) {
			return false;
		}
		// Process calendar
		if (is_array($this->BLOG_OBJ->_posts_by_days)) {
			ksort($this->BLOG_OBJ->_posts_by_days);
		}
		if ($this->BLOG_OBJ->USE_JS_CALENDAR && MAIN_TYPE_USER) {
			$CALENDAR_OBJ = main()->init_class("js_calendar", "classes/");
		}
		if (is_object($CALENDAR_OBJ) && !empty($this->BLOG_OBJ->_posts_by_days)) {
			$CALENDAR_OBJ->_set_params(array(
				"selected_dates"	=> array_keys($this->BLOG_OBJ->_posts_by_days),
				"on_select_link"	=> process_url("./?object=".BLOG_CLASS_NAME."&action=show_posts_archive&id=".($this->BLOG_OBJ->HIDE_TOTAL_ID ? "" : $user_info["id"]."-")."{id}"),
				"start_date"		=> array_shift(array_keys($this->BLOG_OBJ->_posts_by_days)),
				"end_date"			=> array_pop(array_keys($this->BLOG_OBJ->_posts_by_days)),
				"cur_month"			=> $this->BLOG_OBJ->CUR_YEAR."-".$this->BLOG_OBJ->CUR_MONTH."-".($this->BLOG_OBJ->CUR_YEAR != date("Y") || $this->BLOG_OBJ->CUR_MONTH != date("m") ? "01" : date("d")),
			));
		}
		// Prepare archive navigation
		$_archive_tmp	= array();
		$archive_nav	= array();
		foreach ((array)$this->BLOG_OBJ->_posts_by_days as $_date => $_num_posts) {
			$_archive_tmp[substr($_date, 0, 4)][substr($_date, 5, 2)] += $_num_posts;
		}
		krsort($_archive_tmp);
		foreach ((array)$_archive_tmp as $_year => $_posts_by_months) {
			krsort($_posts_by_months);
			if ($_year != date("Y") || ($this->BLOG_OBJ->CUR_YEAR != date("Y") && !$this->BLOG_OBJ->ARCHIVE_NAV_FULL)) {
				$archive_nav[$_year] = array(
					"is_year"	=> 1,
					"year"		=> $_year,
					"name"		=> "",
					"link"		=> "./?object=".BLOG_CLASS_NAME."&action=show_posts_archive&id=".($this->BLOG_OBJ->HIDE_TOTAL_ID ? "" : $user_info["id"]."-").$_year,
					"num_posts"	=> (int)array_sum($_posts_by_months),
				);
			}
			// Skip months from other years
			if ($_year != $this->BLOG_OBJ->CUR_YEAR && !$this->BLOG_OBJ->ARCHIVE_NAV_FULL) {
				continue;
			}
			foreach ((array)$_posts_by_months as $_month => $_num_posts) {
				$archive_nav[$_year."-".$_month] = array(
					"is_year"	=> 0,
					"year"		=> $_year,
					"name"		=> date("F", strtotime($_year."-".$_month."-01")),
					"link"		=> "./?object=".BLOG_CLASS_NAME."&action=show_posts_archive&id=".($this->BLOG_OBJ->HIDE_TOTAL_ID ? "" : $user_info["id"]."-").$_year."-".$_month,
					"num_posts"	=> intval($_num_posts),
				);
			}
		}
		// Archive nav array for widgets
		if ($params["for_widgets"]) {
			$replace = array(
				"archive_nav"		=> $archive_nav ? $archive_nav : "",
				"all_posts_link"	=> !empty($this->BLOG_OBJ->_latest_posts) ? "./?object=".BLOG_CLASS_NAME."&action=show_posts".($this->BLOG_OBJ->HIDE_TOTAL_ID ? "" : "&id=".$user_info["id"]) : "",
				"latest_posts"		=> $this->BLOG_OBJ->_latest_posts,
			);
			return tpl()->parse(BLOG_CLASS_NAME."/widget_archive", $replace);
		}
		// Process right column template
		$replace2 = array(
			"user_name"				=> _display_name($user_info),
			"user_profile_link"		=> _profile_link($user_info["id"]),
			"latest_posts"			=> $this->BLOG_OBJ->_latest_posts,
			"archive_date"			=> !empty($this->BLOG_OBJ->CUR_MONTH) ? date("F Y", strtotime($this->BLOG_OBJ->CUR_YEAR."-".$this->BLOG_OBJ->CUR_MONTH."-01")) : "",
			"calendar_code"			=> is_object($CALENDAR_OBJ) ? $CALENDAR_OBJ->_display_code() : "",
			"calendar_container"	=> is_object($CALENDAR_OBJ) ? $CALENDAR_OBJ->_display_container() : "",
			"all_posts_link"		=> !empty($this->BLOG_OBJ->_latest_posts) ? "./?object=".BLOG_CLASS_NAME."&action=show_posts".($this->BLOG_OBJ->HIDE_TOTAL_ID ? "" : "&id=".$user_info["id"]) : "",
			"archive_nav"			=> $archive_nav ? $archive_nav : "",
			"blog_links"			=> $this->_show_blog_links(),
			"custom_cats"			=> $this->_show_custom_cats(),
			"friends_posts"			=> $this->_show_friends_posts(),
			"all_friends_posts_link"=> "./?object=".BLOG_CLASS_NAME."&action=friends_posts&id=".$user_info["id"],
			"rss_friends_button"	=> $this->BLOG_OBJ->_show_rss_link("./?object=".BLOG_CLASS_NAME."&action=rss_for_friends_posts&id=".$user_info["id"], "RSS feed for friends posts"),
		);
		$body = tpl()->parse(BLOG_CLASS_NAME."/right_column", $replace2);
		$GLOBALS['right_block_items'][] = $body;
		return $body;
	}

	/**
	* Prepare blog links for display
	*/
	function _show_blog_links () {
		$raw_blog_links = $this->BLOG_OBJ->BLOG_SETTINGS["blog_links"];
		if (empty($raw_blog_links)) {
			return false;
		}
		// Try to find correct links
		$links_array = $this->BLOG_OBJ->_blog_links_into_array($raw_blog_links);
		// Cut result array
		if (is_array($links_array) && count($links_array) > $this->BLOG_OBJ->MAX_BLOG_LINKS_NUM) {
			foreach ((array)$links_array as $k => $v) {
				if ($i++ >= $this->BLOG_OBJ->MAX_BLOG_LINKS_NUM) unset($links_array[$k]);
			}
		}
		return !empty($links_array) ? $links_array : "";
	}

	/**
	* Prepare custom categories
	*/
	function _show_custom_cats () {
		$raw_custom_cats = $this->BLOG_OBJ->BLOG_SETTINGS["custom_cats"];
		if (empty($raw_custom_cats)) {
			return false;
		}
		// Try to find correct categories
		$cats_array = $this->BLOG_OBJ->_custom_cats_into_array($raw_custom_cats);
		// Cut result array
		if (is_array($cats_array) && count($cats_array) > $this->BLOG_OBJ->MAX_CUSTOM_CATS_NUM) {
			foreach ((array)$cats_array as $k => $v) {
				if ($i++ >= $this->BLOG_OBJ->MAX_CUSTOM_CATS_NUM) unset($cats_array[$k]);
			}
		}
		return !empty($cats_array) ? $cats_array : "";
	}

	/**
	* Prepare friends messages
	*/
	function _show_friends_posts () {
		$user_info = $GLOBALS['user_info'];
		// Get friends
		$FRIENDS_OBJ	= main()->init_class("friends");
		$friends_ids	= is_object($FRIENDS_OBJ) ? $FRIENDS_OBJ->_get_user_friends_ids($user_info["id"]) : false;
		// Stop here if no friends found
		if (empty($friends_ids)) {
			return "";
		}
		// Get users infos
		foreach ((array)user($friends_ids, "full", array("WHERE" => array("active" => 1))) as $A) {
			$users_names[$A["id"]] = _display_name($A);
		} 
		// Try to get latest friends posts
		$Q = db()->query(
			"SELECT 
				`p`.`id` AS `post_id`,
				`p`.`user_id`,
				`p`.`title` AS `post_title`,
				`p`.`add_date` AS `post_date`,
				`p`.`privacy`,
				`p`.`allow_comments`,
				`p`.`num_reads`,
				SUBSTRING(`p`.`text` FROM 1 FOR ".intval($this->BLOG_OBJ->POST_TEXT_PREVIEW_LENGTH).") AS `post_text` 
			FROM `".db('blog_posts')."` AS `p`
				, (SELECT MAX( `id` ) AS `max_id` 
					FROM `".db('blog_posts')."` 
					WHERE `user_id` IN (".implode(",", $friends_ids).") 
						AND `active`=1 
						AND `privacy` NOT IN(9)
					GROUP BY `user_id` 
					ORDER BY `add_date` DESC 
				) AS `sub` 
			WHERE `p`.`id` = `sub`.`max_id`
			ORDER BY `p`.`add_date` DESC 
			LIMIT ".intval($this->BLOG_OBJ->STATS_NUM_FRIENDS_POSTS)
		);
		while ($post_info = db()->fetch_assoc($Q)) {
			$posts_array[$post_info["post_id"]] = array(
				"post_id"		=> intval($post_info["post_id"]),
				"post_link"		=> "./?object=".BLOG_CLASS_NAME."&action=show_single_post&id=".$post_info["post_id"]._add_get(array("page")),
				"post_title"	=> _prepare_html($post_info["post_title"]),
				"post_date"		=> _format_date($post_info["add_date"], "long"),
				"user_id"		=> intval($post_info["user_id"]),
				"user_name"		=> _prepare_html($users_names[$post_info["user_id"]]),
				"profile_link"	=> _profile_link($post_info["user_id"]),
			);
		}
		return !empty($posts_array) ? $posts_array : "";
	}
}
