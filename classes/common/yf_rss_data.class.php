<?php

/**
* RSS data handler
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_rss_data {

	/** @var array @conf_skip Known item field names */
	public $avail_item_fields	= array(
		"title",
		"link",
		"description",
		"date",
		"source",
		"author",
		"comments",
		"guid",
	);
	/** @var array @conf_skip Valid format strings */
	public $avail_formats		= array(
		"RSS0.91",
		"RSS1.0",
		"RSS2.0",
		"MBOX",
		"OPML",
		"ATOM",
		"ATOM0.3",
		"HTML",
		"JS",
	);
	/** @var string */
	public $FEEDS_CACHE_PATH	= "uploads/rss_cache/";
	/** @var string */
	public $AGGR_CACHE_PATH	= "uploads/rss_aggregator_cache/";
	/** @var string */
	public $DOMIT_RSS_PATH		= "libs/domit/xml_domit_rss.php";
	/** @var bool Use serialized cache */
	var	$USE_ARRAY_CACHE	= true;
	/** @var @string Leave empty for default */
	public $SHOW_RSS_ENCODING	= "utf-8";
	/** @var @int Set to 0 to disable truncation */
	public $DESC_TRUNC_SIZE	= 500;

	/**
	* Framework constructor
	*/
	function _init () {
		// Set paths
		$this->FEEDS_CACHE_PATH	= INCLUDE_PATH. $this->FEEDS_CACHE_PATH;
		$this->AGGR_CACHE_PATH	= INCLUDE_PATH. $this->AGGR_CACHE_PATH;
		$this->DOMIT_RSS_PATH	= YF_PATH. $this->DOMIT_RSS_PATH;
		// Do create cache dirs
		if (!file_exists($this->FEEDS_CACHE_PATH)) {
			_mkdir_m($this->FEEDS_CACHE_PATH);
		}
		if (!file_exists($this->AGGR_CACHE_PATH)) {
			_mkdir_m($this->AGGR_CACHE_PATH);
		}
	}

	/**
	* Show given array as RSS page
	*/
	function show_rss_page ($data = array(), $params = array()) {
		// Connect ot the feed creator
		require_once (YF_PATH."libs/feedcreator/feedcreator.class.php");
		// Instantinate it
		$rss = new UniversalFeedCreator();
		// use cached version if age < 1 hour
		if (!isset($params["use_cached"]) || !empty($params["use_cached"])) {
			$rss->useCached();
		}
		$rss->title			= _prepare_html(!empty($params["feed_title"]) ? $params["feed_title"] : "Site feed title");
		$rss->description	= _prepare_html(!empty($params["feed_desc"]) ? $params["feed_desc"] : "Site feed description");
		// optional feed params
		$rss->descriptionTruncSize		= !empty($params["feed_trunc_size"]) ? intval($params["item_trunc_size"]) : 500;
		$rss->descriptionHtmlSyndicated	= isset($params["feed_insert_html"]) ? (bool)$params["feed_insert_html"] : true;
		$rss->link						= _prepare_html(process_url(!empty($params["feed_url"])	? $params["feed_url"] : "./?object=".$_GET["object"]));
		$this->self_link				= $rss->link;
		$rss->syndicationURL			= _prepare_html(process_url(!empty($params["feed_source"])? $params["feed_source"] : "./?object=".$_GET["object"]."&action=".$_GET["action"].(!empty($_GET["id"]) ? "&id=".$_GET["id"] : "")));
		if (!empty($this->SHOW_RSS_ENCODING)) {
			$rss->encoding = $this->SHOW_RSS_ENCODING;
		}
		// Process feed items
		foreach ((array)$data as $A) {
			$item = new FeedItem();
			// Special processing of date
			if (isset($A["date"])) {
				$A["date"] = intval($A["date"]);
			}
			// Special processing of link (make path absolute)
			if (isset($A["link"])) {
				$A["link"] = process_url($A["link"]);
			}
			// Process known fields
			foreach ((array)$this->avail_item_fields as $field_name) {
				if (isset($A[$field_name]))	{
					$item->$field_name	= $A[$field_name];
				}
			}
			if (!isset($item->guid)) {
				$item->guid = $A["link"]."#".md5($params["feed_source"]."&".$A["date"]."&".$A["title"]."&".$A["author"]);
			}
			// optional params
			$item->descriptionTruncSize			= !empty($params["item_trunc_size"]) ? intval($params["item_trunc_size"]) : $this->DESC_TRUNC_SIZE;
			$item->descriptionHtmlSyndicated	= isset($params["item_insert_html"]) ? (bool)$params["item_insert_html"] : true;
			// optional (enclosure)
			// Sample: $A["enclosure"] = array("url"=>"http://lh3.ggpht.com/smoliarov/Rwygj8ucrbE/AAAAAAAABIA/UkNlwQ7eniw/_200708.jpg","length"=>"65036","type"=>"image/jpeg");
			if (!empty($A["enclosure"]) && is_array($A["enclosure"])) {
				$E = $A["enclosure"];
				$item->enclosure = new EnclosureItem();
				$item->enclosure->url	= _prepare_html($E["url"]);
				$item->enclosure->length= intval($E["length"]);
				$item->enclosure->type	= _prepare_html($E["type"]);
			}
			$rss->addItem($item);
		}
		// Set format of the resulting feed
		$feed_format		= isset($params["feed_format"]) && in_array($params["feed_format"], $this->avail_formats) ? $params["feed_format"] : "RSS2.0";
		$feed_file_name		= $params["feed_file_name"];
		if (!strlen($feed_file_name)) {
			$feed_file_name = md5($_SERVER["HTTP_HOST"]. "_". $rss->title. $rss->description. $this->self_link);
		}
		$feed_file_name = common()->_propose_url_from_name($feed_file_name);
		$feed_cache_path = $this->FEEDS_CACHE_PATH. $feed_file_name. ".xml";
		$feed_cache_dir = dirname($feed_cache_path);
		if (!file_exists($feed_cache_dir)) {
			_mkdir_m($feed_cache_dir);
		}
		// Do create
		$body = $rss->saveFeed($feed_format, $feed_cache_path);
		// Return feed contents or throw it here
		if (!empty($params["return_feed_text"])) {
			return $body;
		} else {
			main()->NO_GRAPHICS = true;
			echo $body;
		}
	}

	/**
	* Get data from RSS feeds and return it as array
	*/
	function fetch_data ($params = array()) {
		// Templates names
		$STPL_MAIN	= !empty($params["stpl_main"]) ? $params["stpl_main"] : "system/common/get_rss_page_main";
		$STPL_ITEM	= !empty($params["stpl_item"]) ? $params["stpl_item"] : "system/common/get_rss_page_item";
		$NUM_ITEMS	= !empty($params["num_items"]) ? $params["num_items"] : 15;
		// Get array of available org types
		$this->_rss_feeds = main()->get_data("rss_feeds");
		// Get latest records from RSS items cache
		$Q = db()->query("SELECT * FROM ".db('rss_items')." ORDER BY feed_id,add_date DESC LIMIT ".intval(count($this->_rss_feeds) * $NUM_ITEMS));
		while ($A = db()->fetch_assoc($Q)) $this->_latest_cached_items[$A["feed_id"]][$A["cache_md5"]] = $A;
		// Process feeds
		foreach ((array) $this->_rss_feeds as $feed_id => $feed_info) {
			$output				= null;
			$max_cache_date		= null;
			$USING_DB_CACHE		= false;
			$DB_CACHE_EXPIRED	= false;
			// Try to find cached items
			if (!empty($this->_latest_cached_items[$feed_id])) {
				foreach ((array)$this->_latest_cached_items[$feed_id] as $item_info) {
					$output["items"][] = array(
						"url"		=> $item_info["link"],
						"title"		=> $item_info["title"],
						"text"		=> $item_info["text"],
						"pub_date"	=> $item_info["pub_date"],
					);
					if ($item_info["pub_date"] > $max_cache_date) {
						$max_cache_date = $item_info["add_date"];
					}
				}
				$USING_DB_CACHE = true;
			}
			// Check if database cache is expired
			$DB_CACHE_EXPIRED = (bool) ($max_cache_date < time() - $feed_info["ttl"]);
			// Parse page info directly from XML
			if (!$USING_DB_CACHE || ($USING_DB_CACHE && $DB_CACHE_EXPIRED)) {
				$output = $this->_get_rss_feed_array($feed_info["url"], $feed_info["ttl"]);
			}
			// Skip empty feeds
			if (empty($output["items"])) {
				continue;
			}
			$items = "";
			// Process items
			foreach ((array)$output["items"] as $item_info) {
				$cache_md5 = md5($item_info["url"]. $item_info["title"]);
				// Do save cache record
				if (!isset($this->_latest_cached_items[$feed_id][$cache_md5])) {
					db()->REPLACE("rss_items", array(
						"feed_id"	=> intval($feed_id),
						"add_date"	=> time(),
						"link"		=> _es($item_info["url"]),
						"title"		=> _es($item_info["title"]),
						"text"		=> _es($item_info["text"]),
						"pub_date"	=> strtotime($item_info["pub_date"]),
						"author"	=> _es($item_info["author"]),
						"cache_md5"	=> _es($cache_md5),
						"guid"		=> _es($item_info["guid"]),
					));
				}
				// Process template
				$replace2 = array(
					"item_url"	=> $item_info["url"],
					"item_title"=> $item_info["title"],
					"item_text"	=> $item_info["text"],
				);
				$items .= tpl()->parse($STPL_ITEM, $replace2);
			}
			// Process template
			$replace = array(
				"channel_url"	=> $feed_info["url"],
				"channel_title"	=> $feed_info["title"],
				"channel_desc"	=> $feed_info["desc"],
				"items"			=> $items,
			);
			$body .= tpl()->parse($STPL_MAIN, $replace);
		}
		return $body;
	}

	/**
	* Get and display data from given RSS feed
	*/
	function get_data ($url = "", $params = array()) {
		if (!$url) {
			return false;
		}
		// Templates names
		$STPL_MAIN	= !empty($params["stpl_main"]) ? $params["stpl_main"] : "system/common/get_rss_page_main";
		$STPL_ITEM	= !empty($params["stpl_item"]) ? $params["stpl_item"] : "system/common/get_rss_page_item";
		$NUM_ITEMS	= !empty($params["num_items"]) ? $params["num_items"] : 15;
		$TTL		= !empty($params["ttl"]) ? $params["ttl"] : 600;
		$LIMIT		= !empty($params["limit"]) ? $params["limit"] : 10;

		$output = $this->_get_rss_feed_array($url, $TTL);
		// Skip empty feeds
		if (empty($output["items"])) {
			continue;
		}
		$items = "";
		// Process items
		foreach ((array)$output["items"] as $item_info) {
			if ($LIMIT && $i++ > $LIMIT) {
				break;
			}
			$replace2 = array(
				"item_url"	=> $item_info["url"],
				"item_title"=> $item_info["title"],
				"item_text"	=> $item_info["text"],
			);
			$items .= tpl()->parse($STPL_ITEM, $replace2);
		}
		// Process template
		$replace = array(
			"channel_url"	=> $url,
			"channel_title"	=> $params["title"],
			"channel_desc"	=> $params["desc"],
			"items"			=> $items,
		);
		return tpl()->parse($STPL_MAIN, $replace);
	}

	/**
	* Do get data from given feed
	*/
	function _get_rss_feed_array ($rss_url = "", $feed_ttl = 3600, $num_items = 15) {
		// Connect DOMIT library
		require_once ($this->DOMIT_RSS_PATH);
		// Prepare cache params
		if ($this->USE_ARRAY_CACHE) {
			$_url_hash			= md5($rss_url);
			$_obj_cache_path	= $this->AGGR_CACHE_PATH."array_".$_url_hash;
		}
		// Check if we can use array cache instead of loading domit library
		if (!empty($_obj_cache_path) && @file_exists($_obj_cache_path) && @filemtime($_obj_cache_path) > (time() - $feed_ttl)) {
			return @unserialize(file_get_contents($_obj_cache_path));
		} else {
			$rssDoc = new xml_domit_rss_document($rss_url, $this->AGGR_CACHE_PATH);
		}
		$success = (bool)$rssDoc;
		// Stop here if no document parsed
		if (!$success) {
			return false;
		}
		$output = array(
			"channel"	=> null,
			"items"		=> null,
		);
		$totalChannels = $rssDoc->getChannelCount();
		// special handling for feed encoding
		$this->_decode_func = $this->_feed_encoding($rssDoc);
		// Process only first channel
		$cur_channel =& $rssDoc->getChannel(0);
		if (empty($cur_channel)) {
			return false;
		}
		$cur_ch_image	= $cur_channel->getImage();
		$_ch_image = array();
		if (is_object($cur_ch_image)) {
			$_ch_image = array(
				"title"	=> $this->_decode_text($cur_ch_image->getTitle()),
				"link"	=> $this->_decode_text($cur_ch_image->getLink()),
				"url"	=> $this->_decode_text($cur_ch_image->getUrl()),
				"width"	=> intval($cur_ch_image->getWidth()),
			);
		}
		$output["channel"] = array(
			"url"		=> $cur_channel->getLink(),
			"title"		=> $this->_decode_text($cur_channel->getTitle()),
			"desc"		=> $this->_decode_text($cur_channel->getDescription()),
			"webmaster"	=> $this->_decode_text($cur_channel->getWebMaster()),
			"image"		=> $_ch_image,
		);
		// Get number of items
		$actual_items = $cur_channel->getItemCount();
		$total_items = ($num_items > $actual_items) ? $actual_items : $num_items;
		// Process items
		for ($j = 0; $j < $total_items; $j++) {
			$currItem	= $cur_channel->getItem($j);
			$cur_enc	= $currItem->getEnclosure();
			$_enclosure = array();
			if (is_object($cur_enc)) {
				$_enclosure = array(
					"url"		=> $this->_decode_text($cur_enc->getUrl()),
					"length"	=> intval($cur_enc->getLength()),
					"type"		=> $this->_decode_text($cur_enc->getType()),
				);
			}
			$output["items"][$j] = array(
				"url"		=> $currItem->getLink(),
				"title"		=> $this->_decode_text($currItem->getTitle()),
				"text"		=> $this->_decode_text($currItem->getDescription()),
				"pub_date"	=> $currItem->getPubDate(),
				"author"	=> $this->_decode_text($currItem->getAuthor()),
				"comments"	=> $this->_decode_text($currItem->getComments()),
				"enclosure"	=> $_enclosure,
			);
		}
		// Do cache array
		if ($this->USE_ARRAY_CACHE) {
			file_put_contents($_obj_cache_path, serialize($output));
		}
		return $output;
	}

	/**
	* Special handling for newfeed encoding and possible conflicts with page encoding and PHP version
	*/
	function _feed_encoding($rssDoc) {
		// test if PHP 5
		if (phpversion() >= 5) {
			// test if page is utf-8
			if (strpos(_ISO,'utf') !== false || strpos(_ISO,'UTF') !== false) {
				$encoding = 'html_entity_decode';
			} else {
			// non utf-8 page
				$encoding = 'utf8_decode';
			}
		} else {
		// handling for PHP 4
			// determine encoding of feed
			$text 		= $rssDoc->toNormalizedString(true);
			$text 		= substr($text, 0, 100);
			$utf8 		= strpos($text, 'encoding=&quot;utf-8&quot;');
			// test if feed is utf-8
			if ($utf8 !== false) {
				// test if page is utf-8
				if (strpos(_ISO,'utf')!== false || strpos(_ISO,'UTF') !== false) {
					$encoding = 'html_entity_decode';
				} else {
					// non utf-8 page
					$encoding = 'utf8_decode';
				}
			} else {
			// handling for non utf-8 feed
				// test if page is utf-8
				if (strpos(_ISO,'utf') !== false || strpos(_ISO,'UTF') !== false) {
					$encoding = 'utf8_encode';
				} else {
					// non utf-8 page
					$encoding = 'html_entity_decode';
				}
			}
		}
		return $encoding;
	}

	/**
	* Do decode text from feed using some tricks
	*/
	function _decode_text($text = "") {
		$func_name = $this->_decode_func;
		return str_replace('&apos;', "'", html_entity_decode($func_name($text)));
	}
}
