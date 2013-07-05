<?php

/**
* RSS aggregator
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_aggregator {

	/** @var array known item field names @conf_skip */
	public $avail_item_fields	= array(
		"title", 
		"link", 
		"description", 
		"date", 
		"source", 
		"author",
		"guid",
	);
	/** @var string */
	public $AGGR_CACHE_PATH	= "uploads/rss_aggregator_cache/";
	/** @var string */
	public $DOMIT_RSS_PATH		= "libs/domit/xml_domit_rss.php";
	/** @var bool Use serialized cache */
	var	$USE_ARRAY_CACHE	= true;
	/** @var bool */
	public $CONVERT_TO_UTF8	= true;
	/** @var bool Auto-approve posts */
	public $AUTO_APPROVE_POSTS	= true;
	/** @var int Limit number of aggregated records from one feed at one time */
	public $LIMIT_FROM_FEED	= 5; // Set to "0" to disable
	/** @var int Limit minimal text length to aggregate */
	public $MIN_TEXT_LENGTH	= 0; // Set to "0" to disable

	/**
	* Framework constructor
	* 
	*/
	function _init () {
		// Set paths
		$this->AGGR_CACHE_PATH	= INCLUDE_PATH. $this->AGGR_CACHE_PATH;
		if (!file_exists($this->AGGR_CACHE_PATH)) {
			_mkdir_m($this->AGGR_CACHE_PATH);
		}
		$this->DOMIT_RSS_PATH	= YF_PATH. $this->DOMIT_RSS_PATH;

		$this->CONVERTER_OBJ = main()->init_class("convert_charset", "classes/");
	}

	/**
	* Do aggregate new info from feeds
	* 
	*/
	function _do_cron_job($force_check = false) {
		$aggregate_infos= array();
		$rss_results	= array();
		$hashes_for_sql = array();
		$hashes_by_feed = array();
		$existed_hashes = array();
		// Get feeds needed to be processed now
		$Q = db()->query("SELECT * FROM ".db('rss_feeds')." WHERE active='1'".($force_check ? "" : " AND last_checked + ttl < ".time()));
		while ($A = db()->fetch_assoc($Q)) {
			$feeds_to_check[$A["id"]] = $A;
			// Get and prepare aggregate info
			$aggregate_infos[$A["id"]] = @eval("return ".$A["aggregate_info"].";");
		}
		// First we nned to collect array of items hashes to check if we already saved them
		foreach ((array)$feeds_to_check as $A) {
			$feed_id = $A["id"];
			// Get latest RSS feed contents
			$rss_results[$feed_id] = $this->_get_rss_feed_data($A["url"]);
			$result = $rss_results[$feed_id];
			// Skip channel if we have empty items
			if (empty($result["items"])) {
				continue;
			}
			foreach ((array)$result["items"] as $_counter => $_item) {
				if ($this->LIMIT_FROM_FEED && $_counter >= $this->LIMIT_FROM_FEED) {
					break;
				}
				$_cache_md5 = md5($_item["url"]. $_item["title"]. $_item["pub_date"]);
				$hashes_for_sql[$_cache_md5] = $_cache_md5;
				$hashes_by_feed[$feed_id."_".$_counter] = $_cache_md5;
			}
		}
		// Check items in db
		if (!empty($hashes_for_sql)) {
			$Q = db()->query("SELECT cache_md5 FROM ".db('rss_items')." WHERE cache_md5 IN('".implode("','",$hashes_for_sql)."')");
			while ($A = db()->fetch_assoc($Q)) {
				$existed_hashes[$A["cache_md5"]] = $A["cache_md5"];
			}
		}
		unset($hashes_for_sql); // Save some memory
		// Do insert new items into db
		foreach ((array)$feeds_to_check as $A) {
			$feed_id = $A["id"];
			$result = $rss_results[$feed_id];
			// Skip channel if we have empty items
			if (empty($result["items"])) {
				continue;
			}
			$feed_encoding = $result["channel"]["encoding"];
			foreach ((array)$result["items"] as $_counter => $_item) {
				if ($this->MIN_TEXT_LENGTH && $_item["text"] < $this->MIN_TEXT_LENGTH) {
					continue;
				}
				if ($this->LIMIT_FROM_FEED && $_counter >= $this->LIMIT_FROM_FEED) {
					break;
				}
				$_cache_md5 = $hashes_by_feed[$feed_id."_".$_counter];
				// Check if we already have this item in db
				if (empty($_cache_md5) || isset($existed_hashes[$_cache_md5])) {
					continue;
				}
				// Do convert given text encoding to utf-8 if needed
				if ($this->CONVERT_TO_UTF8 && !empty($feed_encoding) && $feed_encoding != "utf-8") {
					$_item["title"]	= $this->CONVERTER_OBJ->go($_item["title"],	$feed_encoding, "utf-8");
					$_item["text"]	= $this->CONVERTER_OBJ->go($_item["text"],	$feed_encoding, "utf-8");
				}
				// Save aggregated data
				$aggregate_saved_id = $aggregate_infos[$feed_id] ? $this->_save_aggregated_data($aggregate_infos[$feed_id], $_item) : 0;
				// Do save fetched RSS item
				db()->REPLACE("rss_items", array(
					"feed_id"	=> intval($feed_id),
					"add_date"	=> time(),
					"link"		=> _es($_item["url"]),
					"title"		=> _es($_item["title"]),
					"text"		=> _es($_item["text"]),
					"pub_date"	=> strtotime($_item["pub_date"]),
					"author"	=> _es($_item["author"]),
					"cache_md5"	=> _es($_cache_md5),
					"guid"		=> _es($_item["guid"]),
					"aggregate_saved" => _es($aggregate_saved_id ? $aggregate_infos[$feed_id]["object"]."->".$aggregate_saved_id : ""),
				));
			}
		}
		// Do update feeds last check and modification time
		foreach ((array)$feeds_to_check as $A) {
			$feed_id = $A["id"];
			$result = $rss_results[$feed_id];
			// Do update last channel visit
			db()->UPDATE("rss_feeds", array(
				"last_checked"	=> time(),
				"last_modified"	=> strtotime($result["channel"]["last_modified"]),
			), "id=".intval($feed_id));
		}
	}

	/**
	* Do save fetched data into appropriate location
	*/
	function _save_aggregated_data($info = array(), $item_data = array()) {
		if (empty($info) || empty($item_data)) {
			return false;
		}
		$title	= $item_data["title"];
		$text	= $item_data["text"];
		// Switch between known aggregate targets
		if ($info["object"] == "blog") {
			$text .= "\r\n\r\nsource: [url]".$item_data["url"]."[/url]";
			$title	= strip_tags($title);
			$text	= strip_tags($text);
			db()->INSERT("blog_posts", array(
				"user_id"	=> intval($info["user_id"]),
				"cat_id"	=> intval($info["cat_id"]),
				"title"		=> _es($title),
				"text"		=> _es($text),
				"add_date"	=> strtotime($item_data["pub_date"]),
				"active"	=> $this->AUTO_APPROVE_POSTS ? 1 : 0,
			));
			return db()->INSERT_ID();
		}
		// Not recognized aggregate target object
		return 0;
	}

	/**
	* Do get data from given feed
	*/
	function _get_rss_feed_data ($rss_url = "", $feed_ttl = 3600, $num_items = 15) {
		// Connect DOMIT library
		require_once ($this->DOMIT_RSS_PATH);
		// Prepare cache params
		$_url_hash			= md5($rss_url);
		if ($this->USE_ARRAY_CACHE) {
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
		// Get feed encoding
		$feed_encoding = "utf-8";
		if (preg_match("/<\?xml [^?>]+? encoding=\"([^\"]+?)\"\?>/", ltrim(file_get_contents($this->AGGR_CACHE_PATH.$_url_hash)), $_m)) {
			if (!empty($_m[1])) {
				$feed_encoding = strtolower($_m[1]);
			}
		}
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
			"url"			=> $cur_channel->getLink(),
			"title"			=> $this->_decode_text($cur_channel->getTitle()),
			"desc"			=> $this->_decode_text($cur_channel->getDescription()),
			"webmaster"		=> $this->_decode_text($cur_channel->getWebMaster()),
			"last_modified"	=> $cur_channel->getLastBuildDate(),
			"image"			=> $_ch_image,
			"encoding"		=> $feed_encoding,
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
			$_guid	= "";
			$cur_guid_obj = $currItem->getGUID();
			if (is_object($cur_guid_obj)) {
				$_guid = $cur_guid_obj->getGuid();
			}
			$output["items"][$j] = array(
				"url"		=> $currItem->getLink(),
				"title"		=> $this->_decode_text($currItem->getTitle()),
				"text"		=> $this->_decode_text($currItem->getDescription()),
				"pub_date"	=> $currItem->getPubDate(),
				"author"	=> $this->_decode_text($currItem->getAuthor()),
				"comments"	=> $this->_decode_text($currItem->getComments()),
				"guid"		=> $_guid,
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
