<?php

/**
* Articles search engine
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_articles_search {
	
	/**
	* Main processing method
	*/
	function _go($display_filter_box = true) {
// TODO: add parsing of $_GET["cat_id"] and $_GET["user_id"]

		if ($_GET["q"] == "results" && module('articles')->USE_FILTER) {
			module('articles')->clear_filter(true);
			module('articles')->save_filter(true);
			unset($_GET["q"]);
		}
		if (!$_GET["id"]) {
			$_GET["id"] = "all";
		}
		// Get unique blog posters
		$filter_sql = module('articles')->USE_FILTER ? module('articles')->_create_filter_sql() : "";
		$sql = "SELECT * FROM ".db('articles_texts')." WHERE status = 'active' ".$filter_sql;
		$path = "./?object=".'articles'."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : "&q=results");
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $path, null, module('articles')->VIEW_ALL_ON_PAGE, 0, "", 0);
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
				"title"				=> module('articles')->_format_text($A["title"]),
				"user_name"			=> _prepare_html($author_name),
				"user_profile_link"	=> $A["is_own_article"] ? _profile_link($user_info) : "",
				"view_link"			=> "./?object=".'articles'."&action=view&id=".$A["id"]. (MAIN_TYPE_ADMIN ? _add_get(array("page")) : ""),
				"add_date"			=> _format_date($A["add_date"]),
				"summary"			=> module('articles')->_format_text($summary),
				"num_reads"			=> intval($A["views"]),
				"cat_name"			=> _prepare_html(module('articles')->_articles_cats[$A["cat_id"]]["name"]),
				"cat_link"			=> module('articles')->_cat_link($A["cat_id"]),
			);
			$items .= tpl()->parse('articles'."/search_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"back_url"		=> "./?object=".'articles'."&action=show". (MAIN_TYPE_ADMIN ? _add_get(array("page")) : ""),
			"filter"		=> $display_filter_box ? module('articles')->_show_filter() : "",
			"custom_header"	=> module('articles')->_custom_search_header,
			"custom_content"=> module('articles')->_custom_search_content,
		);
		return tpl()->parse('articles'."/search_main", $replace);
	}
}
