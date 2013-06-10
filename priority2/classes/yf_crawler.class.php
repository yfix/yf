<?php

/**
* Simple internal crawler (intended to use for saving dynamic content as several linked static pages)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_crawler {

	/** @var string Saved files extension */
	public $LINKS_ADD_EXT = ".html";
	/** @var string Default deepness level. Set to -1 for unlimited */
//	public $DEF_MAX_LEVEL = 2;
//	public $DEF_MAX_LEVEL = -1;

	/**
	* Constructor
	*/
	function _init() {
		if (!isset($GLOBALS['_CRAWLER']['TMP_PATH'])) {
//			$GLOBALS['_CRAWLER']['TMP_PATH'] = INCLUDE_PATH."uploads/tmp/crawler_".abs(crc32(microtime(true)))."/";
			$GLOBALS['_CRAWLER']['TMP_PATH'] = INCLUDE_PATH."uploads/tmp/crawler_devel/";
			_mkdir_m($GLOBALS['_CRAWLER']['TMP_PATH']);
			_mkdir_m($GLOBALS['_CRAWLER']['TMP_PATH']."css/");
			_mkdir_m($GLOBALS['_CRAWLER']['TMP_PATH']."img/");
		}
// TODO: ability to change it dynamically
		$GLOBALS['_CRAWLER']['WEB_PATH'] = WEB_PATH;
//		$GLOBALS['_CRAWLER']['WEB_PATH'] = "http://of.dev/bereginya-rodu.org/";

		$this->DIR_OBJ = main()->init_class("dir", "classes/");

// TODO: 	foreach ((array)common()->_multi_request($my_urls_array) as $_url => $_response)
	}

	/**
	* Crawl from given url
	*/
	function go($url = "", $level = null, $pattern_include = "", $pattern_exclude = "") {
		$func_name = __FUNCTION__;
		if (!strlen($url)) {
			$url = $GLOBALS['_CRAWLER']['WEB_PATH'];
//			return false;
		}
		if (isset($GLOBALS['_CRAWLER']['VISITED'][$url])) {
			return false;
		}
		if ($this->DEF_MAX_LEVEL != -1 && $level > $this->DEF_MAX_LEVEL) {
			return false;
		}
		$text = common()->get_remote_page($url);
		$text = $this->_pre_clean_text($text);

		$GLOBALS['_CRAWLER']['VISITED'][$url] = 1;

		// Save current page to disk
		$new_name = $this->_page_name_from_url($url);
		file_put_contents($GLOBALS['_CRAWLER']['TMP_PATH']. $new_name, str_replace($GLOBALS['_CRAWLER']['WEB_PATH'], "/", $text));

		// CSS goes here
		foreach ((array)$this->_collect_css_urls($text) as $_link => $_counter) {
			$new_name = $this->_page_name_from_url($_link, true, "css/");
			$new_path = $GLOBALS['_CRAWLER']['TMP_PATH']. $new_name;
			file_put_contents($new_path, common()->get_remote_page($_link));
		}

		// Images goes here
		foreach ((array)$this->_collect_images_urls($text) as $_link => $_counter) {
			$new_name = $this->_page_name_from_url($_link, true, "img/");
			$new_path = $GLOBALS['_CRAWLER']['TMP_PATH']. $new_name;
			file_put_contents($new_path, common()->get_remote_page($_link));
		}

		$links = $this->_collect_links($text);

		// Clean some memory
		unset($text);

		// Get other page recursively
		foreach ((array)$links as $_link => $_counter) {
			if ($this->_skip_by_pattern($_link, $pattern_include, $pattern_exclude)) {
				continue;
			}
			$this->$func_name($_link, $level + 1, $pattern_include, $pattern_exclude);
		}
		// Last action on the top level
		if (is_null($level)) {
			$this->_replace_links();

// TODO: delete empty "img/" and "css/" dirs
//			$this->DIR_OBJ->delete_dir($GLOBALS['_CRAWLER']['TMP_PATH'], true);

			return "<pre>".print_r($GLOBALS['_CRAWLER'], 1)."</pre>";
		}
	}

	/**
	* Convert URL into saveable page name
	*/
	function _page_name_from_url($url = "", $leave_ext = false, $name_prefix = "") {
		if (!strlen($url)) {
			return "";
		}
		// Get from cache
		if (isset($GLOBALS['_CRAWLER']['NAMES'][$url])) {
			return $GLOBALS['_CRAWLER']['NAMES'][$url];
		}
		// Cut anything after "#" symbol
		$_sharp_pos = strpos($url, "#");
		if (false !== $_sharp_pos) {
			$url = substr($url, 0, $_sharp_pos);
		}
		$new_name = substr($url, strlen($GLOBALS['_CRAWLER']['WEB_PATH']));
		// Cut extension
		$_ext = common()->get_file_ext($url);
		if (strlen($_ext)) {
			$new_name = substr($new_name, 0, -(strlen($_ext) + 1));
		}
// TODO: need to check if such name already created and create new one
		$new_name = str_replace(array(
			"object=",
			"action=",
			"id=",
			"page=",
			"task=",
			"templates/user/",
			"templates/admin/",
			"templates/",
			"uploads/",
			"images/"
		), "_", $new_name);
		$new_name = trim(str_replace("\\", "/", trim($new_name)), "/");
		$new_name = preg_replace("/[^0-9a-z]/ims", "_", $new_name);
		$new_name = trim(trim($new_name), "_");
		$new_name = preg_replace("/[_]+/", "_", $new_name);

		if (!strlen($new_name)) {
			$new_name = "index";
		}
		$new_name = $name_prefix. $new_name;
		// Add auto extension
		if (!$leave_ext) {
			$new_name .= $this->LINKS_ADD_EXT;
		} else {
			$new_name .= (strlen($_ext) ? ".".$_ext : "");
		}

		$GLOBALS['_CRAWLER']['NAMES'][$url] = $new_name;

		return $new_name;
	}

	/**
	* Clean text before start to process it
	*/
	function _pre_clean_text($text = "") {
		$text = str_replace("\r", "", $text);
		$text = preg_replace("/<script[^>]*?>.*?<\/script>/ims", "", $text);
/*		$text = preg_replace("/<style[^>]*?>.*?<\/style>/ims", "", $text);*/
		$text = preg_replace("/<noscript[^>]*?>.*?<\/noscript>/ims", "", $text);
/*		$text = preg_replace("/<link[^>]*?>/ims", "", $text);*/
		$text = preg_replace("/<\!--.*?-->/ims", "", $text);
//		$text = preg_replace("/\s{2,}/ims", " ", $text);
		$text = preg_replace("/[\n]{2,}/ims", "\n", $text);

		// Normalize links
		$text = preg_replace("/\shref\s*=\s*[\"\']{0,1}([a-z0-9_\-\.\#\&\:\/]+)[\"\']{0,1}/ims", " href=\"\\1\"", $text);
		$text = preg_replace("/\@import\s{1,}url\([\"\']{0,1}([a-z0-9_\-\.\#\&\:\/]+?)[\"\']{0,1}\)/ims", "@import url(\"\\1\")", $text);
		$text = preg_replace("/<img\s+([^>]*?)src\s{0,}=\s{0,}[\"\']{0,1}([a-z0-9_\-\.\#\&\:\/]+?\.(gif|png|jpg|jpeg))[\"\']{0,1}/ims", "<img src=\"\\2\"\\1", $text);

		$text = preg_replace("/ href=\"\.\/([^\"]+)\"/ims", " href=\"".$GLOBALS['_CRAWLER']['WEB_PATH']."\\1\"", $text);

//		preg_match_all("/href\s*=\s*[\"\']{0,1}([a-z0-9_\-\.\#\&\:\/]+)[\"\']{0,1}/ims", $text, $m);

		return $text;
	}

	/**
	* Collect all links
	*/
	function _collect_links($text = "") {
		$links = array();

		$_link_pattern = "/<a\s+href\s{0,}=\s{0,}[\"\']{1}([^\"\'>]+?)[\"\']{1}/ims";
		preg_match_all($_link_pattern, $text, $m);

		$_web_path_length = strlen($GLOBALS['_CRAWLER']['WEB_PATH']);

		foreach ((array)$m[1] as $k => $v) {
			if (substr($v, 0, $_web_path_length) != $GLOBALS['_CRAWLER']['WEB_PATH']) {
				continue;
			}
			// Cut anything after "#" symbol
			$_sharp_pos = strpos($v, "#");
			if (false !== $_sharp_pos) {
				$v = substr($v, 0, $_sharp_pos);
			}
			$links[$v]++;
			// Global counter
			$GLOBALS['_CRAWLER']['LINKS'][$v]++;
		}
		arsort($links);

		return $links;
	}

	/**
	* Collect CSS
	*/
	function _collect_css_urls($text = "") {
		$css = array();

		$_web_path_length = strlen($GLOBALS['_CRAWLER']['WEB_PATH']);

		$_css_pattern = "/\@import\s{1,}url\([\"\']{0,1}([a-z0-9_\-\.\#\&\:\/]+?)[\"\']{0,1}\)/ims";
		preg_match_all($_css_pattern, $text, $m);

		foreach ((array)$m[1] as $k => $v) {
			if (substr($v, 0, $_web_path_length) != $GLOBALS['_CRAWLER']['WEB_PATH']) {
				continue;
			}
			// Cut anything after "#" symbol
			$_sharp_pos = strpos($v, "#");
			if (false !== $_sharp_pos) {
				$v = substr($v, 0, $_sharp_pos);
			}
			$css[$v]++;
			// Global counter
			$GLOBALS['_CRAWLER']['CSS'][$v]++;
		}

		$_link_rel_pattern = "/<link rel=\"stylesheet\"[^>]* href=\"([a-z0-9_\-\.\#\&\:\/]+?)\"/ims";
		preg_match_all($_link_rel_pattern, $text, $m);

		foreach ((array)$m[1] as $k => $v) {
			if (substr($v, 0, $_web_path_length) != $GLOBALS['_CRAWLER']['WEB_PATH']) {
				continue;
			}
			// Cut anything after "#" symbol
			$_sharp_pos = strpos($v, "#");
			if (false !== $_sharp_pos) {
				$v = substr($v, 0, $_sharp_pos);
			}
			$css[$v]++;
			// Global counter
			$GLOBALS['_CRAWLER']['CSS'][$v]++;
		}

		arsort($css);
		return $css;
	}

	/**
	* Collect images URLs
	*/
	function _collect_images_urls($text = "") {
		$images = array();

		$_images_pattern = "/<\img\s+[^>]*?src\s{0,}=\s{0,}[\"\']{0,1}([a-z0-9_\-\.\#\&\:\/]+?\.(gif|png|jpg|jpeg))[\"\']{0,1}/ims";
		preg_match_all($_images_pattern, $text, $m);

		$_web_path_length = strlen($GLOBALS['_CRAWLER']['WEB_PATH']);

		foreach ((array)$m[1] as $k => $v) {
			if (substr($v, 0, $_web_path_length) != $GLOBALS['_CRAWLER']['WEB_PATH']) {
				continue;
			}
			// Cut anything after "#" symbol
			$_sharp_pos = strpos($v, "#");
			if (false !== $_sharp_pos) {
				$v = substr($v, 0, $_sharp_pos);
			}
			$images[$v]++;
			// Global counter
			$GLOBALS['_CRAWLER']['IMAGES'][$v]++;
		}
		arsort($images);

		return $images;
	}

	/**
	* Replace links from old to new ones in all saved files
	*/
	function _replace_links() {
		$replace = array();

		$stop_list = array("", "/", "index".$this->LINKS_ADD_EXT);

		foreach ((array)$GLOBALS['_CRAWLER']['NAMES'] as $_source_url => $_target_url) {
			$_orig_url = $_source_url;
			$_source_url = str_replace($GLOBALS['_CRAWLER']['WEB_PATH'], "/", $_source_url);
			if (in_array($_source_url, $stop_list)) {
				continue;
			}
			$_new_key = "\"".$_source_url."\"";
			$_new_val = "\"".$_target_url."\"";

			$replace["href=".$_new_key]		= "href=".$_new_val;
			if (isset($GLOBALS['_CRAWLER']['IMAGES'][$_orig_url])) {
				$replace["src=".$_new_key]		= "src=".$_new_val;
			} elseif (isset($GLOBALS['_CRAWLER']['CSS'][$_orig_url])) {
				$replace["url(".$_new_key.")"]	= "url(".$_new_val.")";
			}
		}
		uksort($replace, array(&$this, "_sort_by_length"));

		$files_path = $GLOBALS['_CRAWLER']['TMP_PATH'];
		$files = $this->DIR_OBJ->scan_dir($files_path, true, "-f", "/(svn|git)/i", 0);

		$this->_replace_in_files($files, array_keys($replace), array_values($replace));
// TODO: list not replaced links (possibly broken)
	}

	/**
	* Replace given text in selected files
	*/
	function _replace_in_files($files, $_find = "", $_replace = null) {
		if (!$files) {
			return array();
		}
		foreach ((array)$files as $_id => $_file_path) {
			$contents = file_get_contents($_file_path);
			if (!$_find || is_null($_replace)) {
				unset($files[$_id]);
				continue;
			}
			$contents = str_replace($_find, $_replace, $contents);
			file_put_contents($_file_path, $contents);
		}
		return $files;
	}

	/**
	* Check if we need to skip current path according to given patterns
	*/
	function _skip_by_pattern ($path = "", $pattern_include = "", $pattern_exclude = "") {
		if (!$path) {
			return false;
		}
		if (!$pattern_include && !$pattern_exclude) {
			return false;
		}
		$_path_clean = trim(str_replace("//", "/", str_replace("\\", "/", $path)));
		// Include files only if they match the mask
		$MATCHED = false;
		if (!empty($pattern_include) && is_string($pattern_include)) {
			if (!preg_match($pattern_include."ims", $_path_clean)) {
				$MATCHED = true;
			}
		}
		// Exclude files from list by mask
		if (!empty($pattern_exclude) && is_string($pattern_exclude)) {
			if (preg_match($pattern_exclude."ims", $_path_clean)) {
				$MATCHED = true;
			}
		}
		return $MATCHED;
	}

	/**
	* Sort array be length desc
	*/
	function _sort_by_length($a, $b) {
		$la = strlen($a);
		$lb = strlen($b);
		if ($la == $lb) {
			return 0;
		}
		return ($la > $lb) ? -1 : 1;
	}
}
