<?php

/**
* Forum integration methods here
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_integration {

	/**
	* Home page integration part
	*/
	function _for_home_page($NUM_NEWEST_FORUM_POSTS = 4, $NEWEST_FORUM_TEXT_LEN = 100, $params = array()){
		$item_stpl_name = $params["for_widgets"] ? "widgets_last_post" : "for_home_page_item";
		$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `status`='a' ORDER BY `created` DESC LIMIT ".intval($NUM_NEWEST_FORUM_POSTS));
		while ($A = db()->fetch_assoc($Q)) {
			$forum_posts[$A["id"]] = $A;	
			$forum_topics_id[$A["topic"]] = $A["topic"];
		}
		if (!empty($forum_topics_id)){
			$Q = db()->query("SELECT * FROM `".db('forum_topics')."` WHERE `id` IN(".implode(",",array_keys($forum_topics_id)).")");
			while ($A = db()->fetch_assoc($Q)) {					
				$forum_topics[$A["id"]] = $A["name"];				
			}
		}
		foreach ((array)$forum_posts as $A) {						
			$text = module('forum')->_cut_bb_codes(_prepare_html($A["text"]));

			if(strlen($text) > $NEWEST_FORUM_TEXT_LEN){											
				$text = _truncate($text, $NEWEST_FORUM_TEXT_LEN, true, true);
			}
			$replace2 = array(
				"user_id" 		=> intval($A["user_id"]),
				"user_name" 	=> $A["user_name"],
				"topic"			=> $forum_topics[$A["topic"]],
				"text"			=> $text,
				"user_link"		=> "./?object=user_profile&action=show&id=".$A["user_id"],
				"topic_link"	=> "./?object=forum&action=view_topic&id=".$A["topic"],
				"created"		=> _format_date($A["created"], "long"),
			);			
			$items .= tpl()->parse(FORUM_CLASS_NAME."/".$item_stpl_name, $replace2);		
		}
		if(empty($items)) {
			return;
		}
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse(FORUM_CLASS_NAME."/for_home_page_main", $replace);
	}

	/**
	* User profile integration part
	*/
	function _for_user_profile($user_id, $MAX_SHOW_FORUM_POSTS){
		$sql = "SELECT `id`,`topic`,`subject`,`user_id`,`user_name`,`created` FROM `".db('forum_posts')."` WHERE `status`='a' AND `user_id`=".intval($user_id);
		list($add_sql, $pages, $this->_num_forum_posts) = common()->divide_pages($sql, "", null, $MAX_SHOW_FORUM_POSTS);
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"num"			=> ++$i,
				"title"			=> _prepare_html($A["subject"]),
				"author"		=> _prepare_html($A["user_name"]),
				"reply_link"	=> "./?object=forum&action=view_topic&id=".$A["topic"],
				"created"		=> _format_date($A["created"]),
				"profile_url"	=> "./?object=".FORUM_CLASS_NAME."&action=show&id=".$A["user_id"],
			);
			$items .= tpl()->parse(FORUM_CLASS_NAME."/for_profile_forum_item", $replace2);
		}
			$value[0] = $items;
			$value[1] = $pages;
		return $value;
	}

	/**
	* general rss
	*/
	function _rss_general(){
	
		$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `status`='a' ORDER BY `created` DESC LIMIT ".intval(module('forum')->NUM_RSS));
		while ($A = db()->fetch_assoc($Q)) {
			$forum_posts[$A["id"]] = $A;	
			$forum_topics_id[$A["topic"]] = $A["topic"];
		}
		if (!empty($forum_topics_id)){
			$Q = db()->query("SELECT * FROM `".db('forum_topics')."` WHERE `id` IN(".implode(",",array_keys($forum_topics_id)).")");
			while ($A = db()->fetch_assoc($Q)) {					
				$forum_topics[$A["id"]] = $A["name"];				
			}
		}
		
		if(!empty($forum_posts)){
			foreach ((array)$forum_posts as $A) {						
				$text = module('forum')->_cut_bb_codes(nl2br(_prepare_html($A["text"])));

				$data[] = array(
					"title"			=> _prepare_html(t("Forum")." - ".$forum_topics[$A["topic"]]),
					"link"			=> process_url("./?object=forum&action=view_topic&id=".$A["topic"]),
					"description"	=> $text,
					"date"			=> $A["created"],
					"author"		=> $A["user_name"],
					"source"		=> "",
				);

			}
		}
		return $data;
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
			"url"	=> "./?object=forum",
		));
		// Get forums from db and divide each by pages
		$sql = "SELECT `id`, `name` FROM `".db('forum_forums')."` ORDER BY `id`";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$sql = "SELECT COUNT(`id`) AS `num` FROM `".db('forum_topics')."` WHERE `forum`='".$A["id"]."' AND `approved`=1 AND `pinned`=0";
			$B = db()->query_fetch($sql);
			$total_pages = ceil(intval($B["num"]) / intval(module('forum')->SETTINGS["NUM_TOPICS_ON_PAGE"]));
			// Process pages
			if ($total_pages > 1) {
				for ($i = 1; $i <= $total_pages; $i++) {
					$OBJ->_store_item(array(
						"url"	=> "./?object=forum&action=view_forum&id=".str_replace(" ", "_", strtolower($A["name"]))."&page=".$i,
					));
				}	
			} else {
				$OBJ->_store_item(array(
					"url"	=> "./?object=forum&action=view_forum&id=".str_replace(" ", "_", strtolower($A["name"])),
				));
			}
		}
		// Get topics from db and divide each by pages
		$sql = "SELECT `id` FROM `".db('forum_topics')."` ORDER BY `id`";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$sql = "SELECT COUNT(`id`) AS `num` FROM `".db('forum_posts')."` WHERE `topic`='".$A["id"]."' AND `status`='a'";
			$B = db()->query_fetch($sql);
			$total_pages = ceil(intval($B["num"]) / intval(module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"]));
			// Process pages
			if ($total_pages > 1) {
				for ($i = 1; $i <= $total_pages; $i++) {
					$OBJ->_store_item(array(
						"url"	=> "./?object=forum&action=view_topic&id=".$A["id"]."&page=".$i,
					));
				}	
			} else {
				$OBJ->_store_item(array(
					"url"	=> "./?object=forum&action=view_topic&id=".$A["id"]."&page=".$i,
				));
			}
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
		$items[]	= $NAV_BAR_OBJ->_nav_item("Home", "./");
		$items[]	= $NAV_BAR_OBJ->_nav_item("Forum", "./?object=".FORUM_CLASS_NAME);
		// Get pregenerated forum items
		$OBJ = module('forum')->_load_sub_module("forum_main_tpl");
		$_FORUM_ITEMS = $OBJ->_show_navigation(true);
		// Prepare links
		foreach ((array)$_FORUM_ITEMS["items_links"] as $_item) {
			$items[]	= $NAV_BAR_OBJ->_nav_item($_item["name"], $_item["link"]);
		}
		// Prepare texts
		foreach ((array)$_FORUM_ITEMS["items_texts"] as $_item) {
			$items[]	= $NAV_BAR_OBJ->_nav_item($_item["name"]);
		}
		return $items;
	}
}
