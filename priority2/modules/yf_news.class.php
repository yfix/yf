<?php

//-----------------------------------------------------------------------------
// News handler module
class yf_news extends yf_module {
	
	//** @var int */
//	public $TRIM_LENGTH	= 100;
	/** @var bool Use bb codes */
	public $USE_BB_CODES	= true;
	/** @var array @conf_skip Params for the comments */
	public $_comments_params = array(
		"return_action" => "full_news"
	);

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Try to override param
//		$this->TRIM_LENGTH = conf('length_trim') ? conf('length_trim') : $this->TRIM_LENGTH;
	}

	//-----------------------------------------------------------------------------
	// Main function
	function show () {
		// Connect pager
		$sql = "SELECT * FROM `".db('news')."` WHERE `active`='1' ORDER BY `add_date` DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Do get news from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) $news_array[$A["id"]] = $A;
		// Try to get info about comments
		if (!empty($news_array)) {
			$num_comments = $this->_get_num_comments(array(
				"objects_ids" => implode(",", array_keys($news_array)),
			));
		}
		// Process records
		foreach ((array)$news_array as $news_info) {
			$news_info["head_text"] = nl2br($news_info["head_text"]);
			$replace2 = array(
				"title"			=> _prepare_html($news_info['title'], 0),
				"add_date"		=> _format_date($news_info['add_date'], "long"),
				"head_text"		=> $news_info["head_text"],
				"full_link"		=> "./?object=".$_GET["object"]."&action=full_news&id=".$news_info['id'],
				"num_comments"	=> intval(isset($num_comments[$news_info['id']]) ? $num_comments[$news_info['id']] : 0),
			);
			$items[$news_info["id"]] = $replace2;
		}
		// Process template
		$replace = array(
			"items"	=> $items,
			"pages"	=> $pages,
			"total"	=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// This function show full news text
	function full_news () {
		$_GET['id'] = intval($_GET['id']);
		$news_info = db()->query_fetch("SELECT * FROM `".db('news')."` WHERE `id`=".intval($_GET['id'])." AND `active`='1'");
		if (!empty($news_info['id'])) {
		
			$OBJ = &main()->init_class("unread");
			$ids = $OBJ->_set_read("news", $_GET["id"]);

			$replace = array(
				"title"		=> _prepare_html($news_info['title'], 0),
				"add_date"	=> _format_date($news_info['add_date'], "long"),
				"full_text"	=> nl2br($news_info["full_text"]),
				"comments"	=> $this->_view_comments(),
			);
			return tpl()->parse($_GET["object"]."/full_news", $replace);
		} else {
			common()->_raise_error("No such news item!");
			return _e();
		}
	}

	/**
	* Display recent news for the home page
	*
	* @access	public
	* @return void
	*/
	function _show_for_home_page ($input = array()) {
		$LIMIT_RECENT = isset($input["num_items"]) ? $input["num_items"] : 4;
		$TRIM_LENGTH = isset($input["trim_length"]) ? $input["trim_length"] : $this->TRIM_LENGTH;
		// Do get news from db
		$Q = db()->query("SELECT `id`,`title`,`head_text`,`add_date` FROM `".db('news')."` WHERE `active`='1' ORDER BY `add_date` DESC LIMIT ".intval($LIMIT_RECENT));
		while ($A = db()->fetch_assoc($Q)) $news_array[$A["id"]] = $A;
		// Try to get info about comments
/*		if (!empty($news_array)) {
			$num_comments = $this->_get_num_comments(array(
				"objects_ids"	=> implode(",", array_keys($news_array)),
				"object_name"	=> $_GET["object"],
			));
		}
*/		// Process items
		foreach ((array)$news_array as $A) {
			$replace2 = array(
				"title"			=> _prepare_html($A['title'], 0),
				"add_date"		=> _format_date($A['add_date'], "long"),
				"head_text"		=> nl2br($A["head_text"]),
				"full_link"		=> "./?object=".__CLASS__."&action=full_news&id=".$A['id'],
//				"num_comments"	=> intval($num_comments[$A['id']]),
			);
			$items .= tpl()->parse(__CLASS__."/home_page_item", $replace2);
		}
		// Do not display this block if there is no latest news
		if (empty($items)) return false;
		// Process template
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse(__CLASS__."/home_page_main", $replace);
	}
	
	function _rss_general(){
		$Q = db()->query("SELECT `id`,`title`,`add_date`,`head_text` FROM `".db('news')."` WHERE `active` = '1' ORDER BY `add_date` DESC LIMIT ".intval($this->NUM_RSS));
		while ($A = db()->fetch_assoc($Q)) {
			
			$data[] = array(
				"title"			=> _prepare_html(t("News")." - ".$A["title"]),
				"link"			=> process_url("./?object=". __CLASS__ ."&action=full_news&id=".$A["id"]),
				"description"	=> nl2br(_prepare_html(strip_tags($A["head_text"]))),
				"date"			=> $A['add_date'],
				"author"		=> "",
				"source"		=> "",
			);
		}
		
		return $data;
	}
	
	/**
	*
	*/
	function _unread () {
	
		if(empty($this->_user_info["last_view"])){
			return;
		}
		
		$Q = db()->query("SELECT `id` FROM `".db('news')."` WHERE `active` = '1' AND `add_date` > ".$this->_user_info["last_view"]);
		while ($A = db()->fetch_assoc($Q)) {
			$ids[$A["id"]] = $A["id"];
		}
		
		$link = process_url("./?object=news&action=view_unread");
		
		$unread = array(
			"count"	=> count($ids),
			"ids"	=> $ids,
			"link"	=> $link,
		);
	
		return $unread;
	}
	
	/**
	*
	*/
	function view_unread () {
		if(empty($this->USER_ID)){
			return;
		}
	
		$OBJ = &main()->init_class("unread");
		$ids = $OBJ->_get_unread("news");
		
		
		if(!empty($ids)){
			$sql		= "SELECT `id`,`title` FROM `".db('news')."` WHERE `id` IN(".implode(",", (array)$ids).")";
			$order_sql	= " ORDER BY `add_date` DESC";
			list($add_sql, $pages, $total) = common()->divide_pages($sql);
			$Q = db()->query($sql.$order_sql.$add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$news_info[$A["id"]] = $A;
			}
		}
		
		$replace = array(
			"items"		=> $news_info,
			"pages"		=> $pages,
		);
		
		return tpl()->parse($_GET["object"]."/unread", $replace);
	}



}
