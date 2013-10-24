<?php

/**
* Blog integration
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blog_integration {

	/**
	* Framework constructor
	*/
	function _init () {
		$this->BLOG_OBJ		= module(BLOG_CLASS_NAME);
	}

	/**
	* Code for home page
	*/
	function _for_home_page($NUM_NEWEST_BLOG_POSTS = 4, $NEWEST_BLOG_TEXT_LEN = 100, $params = array()){
		if (is_array($NUM_NEWEST_BLOG_POSTS)) {
			$NUM_NEWEST_BLOG_POSTS = $NUM_NEWEST_BLOG_POSTS["num"];
		}
		
		$stpl_name = $params["for_widgets"] ? "widgets_last_post" : "for_home_page_item2";

		$Q = db()->query("SELECT * FROM ".db('blog_posts')." WHERE active='1' ORDER BY add_date DESC LIMIT ".intval($NUM_NEWEST_BLOG_POSTS));
		while ($A = db()->fetch_assoc($Q)) {
			$blog_posts[$A["id"]] = $A;	
			$users_id[$A["user_id"]] = $A["user_id"];
		}
		
		if(!empty($blog_posts)){
			$Q = db()->query("SELECT * FROM ".db('blog_settings')." WHERE user_id IN(".implode(",",array_keys($users_id)).")");
			while ($A = db()->fetch_assoc($Q)) {					
				$blogs[$A["user_id"]] = $A;				
			}
		}
		foreach ((array)$blog_posts as $A) {
			$text = $this->BLOG_OBJ->_cut_bb_codes(nl2br(_prepare_html($A["text"])));
			
			if (strlen($text) > $NEWEST_BLOG_TEXT_LEN) {
				$text = _truncate($text, $NEWEST_BLOG_TEXT_LEN, true, true);
			}
			$replace2 = array(
				"user_id" 		=> intval($A["user_id"]),
				"user_name" 	=> $blogs[$A["user_id"]]["user_nick"],	
				"blog_title"	=> _prepare_html($blogs[$A["user_id"]]["blog_title"]),
				"text"			=> $text,
				"user_link"		=> "./?object=user_profile&action=show&id=".$A["user_id"],
				"blog_link"		=> "./?object=blog&action=show_posts&id=".$A["user_id"],
				"add_date"		=> _format_date($A["add_date"], "long"),
				"post_title"	=> _prepare_html($A["title"]),
				"post_link"		=> "./?object=blog&action=show_single_post&id=".$A["id"],
			);			
			$items .= tpl()->parse(BLOG_CLASS_NAME."/".$stpl_name, $replace2);
		}
		if(empty($items)) {
			return;
		}
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse(BLOG_CLASS_NAME."/for_home_page_main2", $replace);
	}

	/**
	* Code for user profile
	*/
	function _for_user_profile($id, $MAX_SHOW_BLOG_POSTS){
		$sql = "SELECT id,id2,user_id,title,add_date FROM ".db('blog_posts')." WHERE user_id=".intval($id)." AND active=1";
		list($add_sql, $pages, $this->_num_blog_posts) = common()->divide_pages($sql, "", null, $MAX_SHOW_BLOG_POSTS);
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"num"		=> ++$i,
				"title"		=> _prepare_html($A["title"]),
				"created"	=> _format_date($A["add_date"]),
				"view_link"	=> "./?object=blog&action=show_single_post&id=".$A["id"],
			);
			$items .= tpl()->parse(BLOG_CLASS_NAME."/for_profile_blog_item", $replace2);
		}
		$value[0] = $items;
		$value[1] = $pages;

		return $value;
	}
	
	/**
	* Hook for RSS
	*/
	function _rss_general(){

		$Q = db()->query("SELECT * FROM ".db('blog_posts')." WHERE active='1' ORDER BY add_date DESC LIMIT ".intval($this->BLOG_OBJ->NUM_RSS));
		while ($A = db()->fetch_assoc($Q)) {
			$blog_posts[$A["id"]] = $A;	
			$users_id[$A["user_id"]] = $A["user_id"];
		}
		
		if(!empty($blog_posts)){
			$Q = db()->query("SELECT * FROM ".db('blog_settings')." WHERE user_id IN(".implode(",",array_keys($users_id)).")");
			while ($A = db()->fetch_assoc($Q)) {					
				$blogs[$A["user_id"]] = $A;				
			}
		}
		
		if(!empty($blog_posts)){
			foreach ((array)$blog_posts as $A) {
				$text = $this->BLOG_OBJ->_cut_bb_codes(nl2br(_prepare_html($A["text"])));
				
				if (strlen($text) > $NEWEST_BLOG_TEXT_LEN) {
					$text = _truncate($text, $NEWEST_BLOG_TEXT_LEN, true, true);
				}

				$data[] = array(
					"title"			=> _prepare_html(t("Blog")." - ".$blogs[$A["user_id"]]["blog_title"]),
					"link"			=> process_url("./?object=blog&action=show_posts&id=".$A["user_id"]),
					"description"	=> $text,
					"date"			=> $A["add_date"],
					"author"		=> $blogs[$A["user_id"]]["user_nick"],
					"source"		=> "",
				);
			}
		}

		return $data;
	}
}
