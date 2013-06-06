<?php

/**
* Articles integration
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_articles_integration {

	/**
	* Framework constructor
	*/
	function _init () {
		$this->ARTICLES_OBJ		= module(ARTICLES_CLASS_NAME);
	}

	/**
	* Code for home page
	*/
	function _for_home_page($NUM_NEWEST_ARTICLE_POST = 4){
		$Q = db()->query("SELECT `id` AS `article_id`,`author_name`,`add_date`,`title`,`summary`,`user_id` 
			FROM `".db('articles_texts')."` 
			WHERE `status` = 'active' 
			ORDER BY `add_date` DESC 
			LIMIT ".intval($NUM_NEWEST_ARTICLE_POST)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"title"			=> _prepare_html($A["title"]),
				"add_date"		=> _format_date($A['add_date'], "long"),
				"summary"		=> nl2br(_prepare_html(_cut_bb_codes($A["summary"]))),
				"full_link"		=> "./?object=".ARTICLES_CLASS_NAME."&action=view&id=".$A["article_id"],
				"user_link"		=> "./?object=user_profile&action=show&id=".$A["user_id"],
				"user_name"		=> $A["author_name"],

			);
			
			$items .= tpl()->parse(ARTICLES_CLASS_NAME."/home_page_item", $replace2);
		}
		
		$replace = array(
			"items"		=> $items,
		);
		
		return tpl()->parse(ARTICLES_CLASS_NAME."/home_page_main", $replace);
	}

	/**
	* Code for user profile
	*/
	function _for_user_profile($id, $MAX_SHOW_ARTICLES){
		$sql = "SELECT `id`,`user_id`,`title`,`add_date` FROM `".db('articles_texts')."` WHERE `user_id`=".intval($id)." AND `status`='active' ORDER BY `add_date` DESC";
		list($add_sql, $pages, $this->_num_articles_posts) = common()->divide_pages($sql, "", null, $MAX_SHOW_ARTICLES);
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"num"		=> ++$i,
				"title"		=> _prepare_html($A["title"]),
				"created"	=> _format_date($A["add_date"]),
				"view_link"	=> "./?object=articles&action=view&id=".$A["id"],
			);
			$items .= tpl()->parse(ARTICLES_CLASS_NAME."/for_profile_item", $replace2);
		}
			$value[0] = $items;
			$value[1] = $pages;
		return $value;

	}
	
	function _rss_general(){
		$Q = db()->query("SELECT `id` AS `article_id`,`author_name`,`add_date`,`title`,`summary` 
			FROM `".db('articles_texts')."` 
			WHERE `status` = 'active' 
			ORDER BY `add_date` DESC 
			LIMIT ".intval($this->ARTICLES_OBJ->NUM_RSS)
		);
		
		while ($A = db()->fetch_assoc($Q)) {
			
			$data[] = array(
				"title"			=> _prepare_html(t("Articles")." - ".$A["title"]),
				"link"			=> process_url("./?object=".ARTICLES_CLASS_NAME."&action=view&id=".$A["article_id"]),
				"description"	=> nl2br(_prepare_html(strip_tags($A["summary"]))),
				"date"			=> $A['add_date'],
				"author"		=> $A["author_name"],
				"source"		=> "",
			);
		}
		
		return $data;
	}
}
