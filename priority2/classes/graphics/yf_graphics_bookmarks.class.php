<?php

/**
* Social bookmarks and RSS handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_graphics_bookmarks {

	/**
	* Display "bookmark it" button
	*/
	function _show_bookmarks_button ($title = "", $url = "", $only_links = 1) {
		$replace = array(
			"bookmark_url"	=> process_url("./?object=dynamic&action=show_bookmarks"),
			"encoded_title"	=> str_replace(" ", "+", smart_htmlspecialchars($title)),
			"encoded_url"	=> urlencode(process_url($url)),
		);
		return tpl()->parse("system/bookmarks_external/compact", $replace);
	}

	/**
	* Display bookmarks links page
	*/
	function _show_bookmarks_extended () {
		main()->NO_GRAPHICS = true;

		$title	= rawurldecode(isset($_GET["id"]) ? $_GET["id"] : $_POST["title"]);
		$url	= rawurldecode(isset($_GET["page"]) ? $_GET["page"] : $_POST["url"]);
		// Prepare title
		$title = str_replace(array(";",",",".",":"," ","/"), "_", $title);
		$title = str_replace("__", "_", $title);
		$title = strtolower(preg_replace("/\W/i", "", $title));
		// Prepare URL
		if (empty($url)) {
			$url = WEB_PATH;
		}
		$url = process_url($url);
		// Process template
		$replace = array(
			"title"			=> _prepare_html($title),
			"encoded_title"	=> str_replace(" ", "+", smart_htmlspecialchars($title)),
			"encoded_url"	=> urlencode($url),
			"only_links"	=> intval((bool)$only_links),
		);
		$body = tpl()->parse("system/bookmarks_external/extended", $replace);
		echo common()->show_empty_page($body);
	}

	/**
	* Display "RSS link" button
	*/
	function _show_rss_button ($feed_name = "", $feed_link = "") {
		// Prevent double javascript injection
		if (!isset($GLOBALS['_num_rss_buttons'])) {
			$GLOBALS['_num_rss_buttons'] = 0;
		}
		$GLOBALS['_num_rss_buttons']++;
		// Prepare template
		$replace = array(
			"extended_link"	=> process_url("./?object=dynamic&action=show_rss"),
			"feed_link"		=> $feed_link,
			"feed_name"		=> _prepare_html($feed_name),
			"encoded_url"	=> urlencode($feed_link),
			"encoded_title"	=> str_replace(" ", "+", smart_htmlspecialchars($feed_name)),
			"need_js"		=> $GLOBALS['_num_rss_buttons'] <= 1 ? 1 : 0,
		);
		return tpl()->parse("system/rss/compact", $replace);
	}

	/**
	* Display "add to RSS readers" page
	*/
	function _show_rss_extended () {
		main()->NO_GRAPHICS = true;

		$title	= rawurldecode(isset($_GET["id"]) ? $_GET["id"] : $_POST["title"]);
		$url	= rawurldecode(isset($_GET["page"]) ? $_GET["page"] : $_POST["url"]);
		// Process template
		$replace = array(
			"feed_link"			=> urlencode($url),
			"feed_name"			=> str_replace(" ", "+", smart_htmlspecialchars($title)),
			"source_feed_link"	=> $url,
			"source_feed_name"	=> _prepare_html($title),
		);
		$body = tpl()->parse("system/rss/extended", $replace);
		echo common()->show_empty_page($body);
	}
}
