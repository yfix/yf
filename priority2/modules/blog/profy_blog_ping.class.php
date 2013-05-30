<?php

/**
* Pinging Google
*
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_blog_ping {

	/**
	* Constructor
	*/
	function _init () {
		$this->BLOG_OBJ		= module(BLOG_CLASS_NAME);
		$this->SETTINGS		= &$this->BLOG_OBJ->SETTINGS;
		$this->USER_RIGHTS	= &$this->BLOG_OBJ->USER_RIGHTS;
	}

	/**
	* Do ping Google
	*/
	function _do_ping($record_id = 0, $blog_id = 0) {
		if (!$this->BLOG_OBJ->ALLOW_PING_GOOGLE || empty($record_id) || empty($blog_id)) {
			return false;
		}
		main()->NO_GRAPHICS = true;
		// Prepare URLs that changed
		$post_html_url	= process_url("./?object=".BLOG_CLASS_NAME."&action=show_posts&id=".$blog_id);
		$post_rss_url	= process_url("./?object=".BLOG_CLASS_NAME."&action=rss_for_single_blog&id=".$blog_id);
		// Switch between ping methods
		if ($this->BLOG_OBJ->PING_METHOD == "xml-rpc") {

			$XML_RPC_OBJ = main()->init_class("xml_rpc", "classes/");
			$XML_RPC_OBJ->USE_COMPACT_PARAMS = true;
			if (DEBUG_MODE) {
				$XML_RPC_OBJ->_XML_RPC_DEBUG = true;
			}
			// Prepare XML-RPC params
			$rpc_url	= "http://blogsearch.google.com/ping/RPC2";
			$rpc_method	= "weblogUpdates.extendedPing";
			$rpc_params = array(
				0	=> SITE_ADV_TITLE,		// Name of site
				1	=> WEB_PATH,			// URL of site
				2	=> $post_html_url,		// URL of the page to be checked for changes
				3	=> $post_rss_url,		// URL of RSS, RDF, or Atom feed
			//	4	=> $tags, 				// Optional a name (or "tag") categorizing your site content. You may delimit multiple values by using the '|' character.
			);
			$result = $XML_RPC_OBJ->xml_rpc_send($rpc_url, $rpc_method, $rpc_params);

		} elseif ($this->BLOG_OBJ->PING_METHOD == "rest") {

			$rest_url = "http://blogsearch.google.com/ping";
			$rest_url .= "?name=".urlencode(SITE_ADV_TITLE);
			$rest_url .= "&url=".urlencode($post_html_url);
			$rest_url .= "&changes_url=".urlencode($post_rss_url);

			$result	= @file_get_contents($rest_url);

			if (DEBUG_MODE) {
// TODO: debug log
			}
		}
	}
}
