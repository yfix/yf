<?php

/**
* Articles statistics page handler
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_articles_stats {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->PARENT_OBJ	= module(ARTICLES_CLASS_NAME);
	}
	
	/**
	* Display total blog stats
	*/
	function _show_stats() {
		// Get latest articles
		
		$sql		= 	
			"SELECT 
				`id` AS `article_id`,
				`user_id`,
				`author_name`,
				`is_own_article`,
				`add_date`,
				`views`,
				`title`,
				`summary` 
			FROM `".db('articles_texts')."` 
			WHERE `status` = 'active'";
			
		$order_sql	= " ORDER BY `add_date` DESC";
		list($add_sql, $last_article_pages, $total) = common()->divide_pages($sql, "", "", intval($this->PARENT_OBJ->STATS_NUM_LATEST));
		$Q = db()->query($sql.$order_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$latest_articles_ids[$A["article_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		
		// Get most commented articles
		$Q = db()->query(
			"SELECT 
				`c`.`object_id` AS `article_id`,
				`a`.`user_id`,
				`a`.`author_name`,
				`a`.`is_own_article`,
				`a`.`add_date` AS `add_date`,
				`a`.`title` AS `title`, 
				`a`.`views`, 
				COUNT(`c`.`id`) AS `num_comments`,
				`a`.`summary` 
			FROM `".db('comments')."` AS `c`,
				`".db('articles_texts')."` AS `a` 
			WHERE `c`.`object_name`='"._es(ARTICLES_CLASS_NAME)."' 
				AND `a`.`status` = 'active' 
				AND `a`.`id`=`c`.`object_id` 
			GROUP BY `c`.`object_id` 
			ORDER BY `num_comments` DESC 
			LIMIT ".intval($this->PARENT_OBJ->STATS_NUM_MOST_COMMENTED)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$articles_comments_ids[$A["article_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get most read articles
		$Q = db()->query(
			"SELECT 
				`id` AS `article_id`,
				`user_id`,
				`author_name`,
				`is_own_article`,
				`title`,
				`add_date`,
				`views`,
				`summary` 
			FROM `".db('articles_texts')."` 
			WHERE `status` = 'active' 
			ORDER BY `views` DESC 
			LIMIT ".intval($this->PARENT_OBJ->STATS_NUM_MOST_READ)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$most_read_ids[$A["article_id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get most active authors
		$Q = db()->query(
			"SELECT 
				COUNT(`id`) AS `num_articles`,
				`author_name`,
				`user_id` 
			FROM `".db('articles_texts')."` 
			WHERE `status` = 'active' 
			GROUP BY `author_name` 
			ORDER BY `num_articles` DESC 
			LIMIT ".intval($this->PARENT_OBJ->STATS_NUM_MOST_ACTIVE)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$top_articles_users[$A["author_name"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get articles by categories	
		$Q = db()->query(
			"SELECT 
				COUNT(`id`) AS `num_articles`,
				`cat_id` 
			FROM `".db('articles_texts')."` 
			WHERE `status` = 'active' 
				AND `cat_id` != 0 
			GROUP BY `cat_id` 
			ORDER BY `num_articles` DESC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$num_articles_by_cats[$A["cat_id"]] = $A["num_articles"];
		}
		// Get user's ids
		foreach ((array)$latest_articles_ids as $A)		$users_ids[$A["user_id"]] = $A["user_id"];
		foreach ((array)$articles_comments_ids as $A) 	$users_ids[$A["user_id"]] = $A["user_id"];
		foreach ((array)$most_read_ids as $A) 			$users_ids[$A["user_id"]] = $A["user_id"];
		foreach ((array)$top_articles_users as $A)	 	$users_ids[$A["user_id"]] = $A["user_id"];
		unset($users_ids[""]);
		// Get users infos and settings
		if (!empty($users_ids))	{
			$this->_users_infos = user($users_ids, array("id","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => 1)));

		}
		// Get number of comments for all processed articles
		$articles_ids = array_merge(
			array_keys((array)$latest_articles_ids),
			array_keys((array)$articles_comments_ids),
			array_keys((array)$most_read_ids)
		);
		if (!empty($articles_ids)) {
			$this->_num_comments = $this->PARENT_OBJ->_get_num_comments(implode(",",$articles_ids));
		}
		$i = 0;
		// Process most active authors
		foreach ((array)$top_articles_users as $A) {
			$author_name = !empty($A["author_name"]) ? $A["author_name"] : _display_name($this->_users_infos[$A["user_id"]]);
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"		=> $i,
				"avatar"			=> $A["is_own_article"] ? _show_avatar($A["user_id"], $this->_users_infos[$A["user_id"]], 1) : "",
				"articles_link"		=> $this->PARENT_OBJ->_user_articles_link($A["user_id"], $A["author_name"]),
				"num_articles"		=> intval($A["num_articles"]),
				"user_name"			=> _prepare_html($author_name),
				"user_profile_link"	=> $A["is_own_article"] ? _profile_link($A["user_id"]) : "",
			);
			$most_active_authors .= tpl()->parse(ARTICLES_CLASS_NAME."/stats_most_active_item", $replace2);
		}
		$i = 0;
		// Process articles groupped by categories
		foreach ((array)$num_articles_by_cats as $cat_id => $num_articles) {
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"		=> $i,
				"cat_link"			=> $this->PARENT_OBJ->_cat_link($cat_id),
				"cat_name"			=> _prepare_html($this->PARENT_OBJ->_articles_cats[$cat_id]["name"]),
				"cat_nav"			=> $this->PARENT_OBJ->CATS_OBJ->_get_nav_by_item_id($cat_id),
				"num_articles"		=> intval($num_articles),
			);
			$articles_by_cats .= tpl()->parse(ARTICLES_CLASS_NAME."/stats_cat_item", $replace2);
		}

		// Process tags
		$this->_tags = $this->PARENT_OBJ->_show_tags(array_keys((array)$latest_articles_ids));

		// Process latest articles
		$latest_articles			= $this->_process_stats_item($latest_articles_ids);
		// Process most commented articles
		$most_commented_articles	= $this->_process_stats_item($articles_comments_ids);
		// Process most read articles
		$most_read_articles			= $this->_process_stats_item($most_read_ids);
		// Process main template
		$replace = array(
			"is_logged_in"				=> intval((bool) $this->PARENT_OBJ->USER_ID),
			"show_own_articles_link"	=> $this->PARENT_OBJ->USER_ID ? "./?object=".ARTICLES_CLASS_NAME."&action=search&user_id=".$this->PARENT_OBJ->USER_ID._add_get(array("page")) : "",
			"manage_link"				=> $this->PARENT_OBJ->USER_ID ? "./?object=".ARTICLES_CLASS_NAME."&action=manage".(MAIN_TYPE_ADMIN ? _add_get(array("page")) : "") : "",
			"search_link"				=> "./?object=".ARTICLES_CLASS_NAME."&action=search".(MAIN_TYPE_ADMIN ? _add_get(array("page")) : ""),
			"latest_articles"			=> $latest_articles,
			"last_article_pages"		=> $last_article_pages,
			"most_active_authors"		=> $most_active_authors,
			"most_commented_articles"	=> $most_commented_articles,
			"most_read_articles"		=> $most_read_articles,
			"articles_by_cats"			=> $articles_by_cats,
		);
		return tpl()->parse(ARTICLES_CLASS_NAME."/stats_main", $replace);
	}
	
	/**
	* Display item for the stats
	*/
	function _process_stats_item($info_array = array()) {
		foreach ((array)$info_array as $A) {
			$summary = $A["summary"];
/*
			if (strlen($summary) > $this->PARENT_OBJ->POST_TEXT_PREVIEW_LENGTH) {
				$post_text = substr($A["post_text"], 0, $this->PARENT_OBJ->POST_TEXT_PREVIEW_LENGTH);
			}
*/
			$author_name = !empty($A["author_name"]) ? $A["author_name"] : _display_name($this->_users_infos[$A["user_id"]]);
			$ARTICLE_ID = intval($A["article_id"] ? $A["article_id"] : $A["id"]);
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"result_num"	=> $i,
				"avatar"		=> _show_avatar($A["user_id"], $this->_users_infos[$A["user_id"]], 1),
				"user_name"		=> _prepare_html($author_name),
				"user_link"		=> $A["user_id"] ? _profile_link($A["user_id"]) : "",
				"view_link"		=> "./?object=".ARTICLES_CLASS_NAME."&action=view&id=".$ARTICLE_ID.(MAIN_TYPE_ADMIN ? _add_get(array("page")) : ""),
				"add_date"		=> _format_date($A["add_date"]),
				"title"			=> $this->PARENT_OBJ->_format_text($A["title"]),
				"summary"		=> $this->PARENT_OBJ->_format_text($summary),
				"credentials"	=> $this->PARENT_OBJ->_format_text($A["credentials"]),
				"num_reads"		=> intval($A["views"]),
				"num_comments"	=> isset($this->_num_comments[$ARTICLE_ID]) ? intval($this->_num_comments[$ARTICLE_ID]) : intval($this->PARENT_OBJ->_num_comments[$ARTICLE_ID]),
				"articles_link"	=> $A["is_own_article"] && $A["user_id"] ? $this->PARENT_OBJ->_user_articles_link($A["user_id"]) : "",
				"num_articles"	=> intval($A["num_articles"]),
				"tags_block"	=> isset($this->_tags[$ARTICLE_ID]) ? $this->_tags[$ARTICLE_ID] : $this->PARENT_OBJ->_tags[$ARTICLE_ID],
			);
			$body .= tpl()->parse(ARTICLES_CLASS_NAME."/stats_item", $replace2);
		}
		return $body;
	}
}
