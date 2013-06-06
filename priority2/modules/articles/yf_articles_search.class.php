<?php

/**
* Articles search engine
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_articles_search {

	/**
	* Constructor
	*/
	function yf_articles_search () {
		$this->PARENT_OBJ	= module(ARTICLES_CLASS_NAME);
	}
	
	/**
	* Main processing method
	*/
	function _go($display_filter_box = true) {
// TODO: add parsing of $_GET["cat_id"] and $_GET["user_id"]

		if ($_GET["q"] == "results" && $this->PARENT_OBJ->USE_FILTER) {
			$this->PARENT_OBJ->clear_filter(true);
			$this->PARENT_OBJ->save_filter(true);
			unset($_GET["q"]);
		}
		if (!$_GET["id"]) {
			$_GET["id"] = "all";
		}
		// Get unique blog posters
		$filter_sql = $this->PARENT_OBJ->USE_FILTER ? $this->PARENT_OBJ->_create_filter_sql() : "";
		$sql = "SELECT * FROM `".db('articles_texts')."` WHERE `status` = 'active' ".$filter_sql;
		$path = "./?object=".ARTICLES_CLASS_NAME."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : "&q=results");
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $this->PARENT_OBJ->VIEW_ALL_ON_PAGE, 0, "", 0);
		// Get contents from db
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$articles[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get authors infos
		if (!empty($users_ids)) {
			$users_infos = user(array_keys($users_ids), array("id","name","login","nick","name","email","profile_url","photo_verified"));
		}
		// Process articles
		foreach ((array)$articles as $A) {
			$user_info	= &$users_infos[$A["user_id"]];
			$summary	= $A["summary"];
			$author_name = !empty($A["author_name"]) ? $A["author_name"] : _display_name($user_info);
			$replace2 = array(
				"title"				=> $this->PARENT_OBJ->_format_text($A["title"]),
				"user_name"			=> _prepare_html($author_name),
				"user_profile_link"	=> $A["is_own_article"] ? _profile_link($user_info) : "",
				"view_link"			=> "./?object=".ARTICLES_CLASS_NAME."&action=view&id=".$A["id"]. (MAIN_TYPE_ADMIN ? _add_get(array("page")) : ""),
				"add_date"			=> _format_date($A["add_date"]),
				"summary"			=> $this->PARENT_OBJ->_format_text($summary),
				"num_reads"			=> intval($A["views"]),
				"cat_name"			=> _prepare_html($this->PARENT_OBJ->_articles_cats[$A["cat_id"]]["name"]),
				"cat_link"			=> $this->PARENT_OBJ->_cat_link($A["cat_id"]),
			);
			$items .= tpl()->parse(ARTICLES_CLASS_NAME."/search_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"back_url"		=> "./?object=".ARTICLES_CLASS_NAME."&action=show". (MAIN_TYPE_ADMIN ? _add_get(array("page")) : ""),
			"filter"		=> $display_filter_box ? $this->PARENT_OBJ->_show_filter() : "",
			"custom_header"	=> $this->PARENT_OBJ->_custom_search_header,
			"custom_content"=> $this->PARENT_OBJ->_custom_search_content,
		);
		return tpl()->parse(ARTICLES_CLASS_NAME."/search_main", $replace);
	}
}
