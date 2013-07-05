<?php

/**
* Rewrite links in the given source module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_rewrite {

	/** @var string @conf_skip "Extensions" of files in the links in rewrite mode */
	public $_rewrite_add_extension = ".html";
	/** @var array @conf_skip Patterns to use */
	public $_replace_patterns		= null;
	/** @var string @conf_skip Links pattern */
	var	$_links_pattern			= "/(action|location|href|src)[\s]{0,1}=[\s]{0,1}[\"\']?(\.\/\?[^\"\'\>\s]+|\.\/)[\"\']?/ims";
	/** @var string @conf_skip Pattern for iframe links */
	var	$_iframe_pattern		= "/(action|location|href)[\s]{0,1}=[\s]{0,1}[\"\']+\.\/\?([^\"\'>\s]*)[\"\']+/ims";
	/** @var bool Force no debug */
	public $FORCE_NO_DEBUG			= false;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* YF module constructor
	*/
	function _init () {
		// If defined constant
		if (defined('REWRITE_ADD_EXT')) {
			$this->_rewrite_add_extension = REWRITE_ADD_EXT;
		}
		// Try to load default patterns if not loaded yet
		if (!isset($GLOBALS['REWRITE_PATTERNS'])) {
			$_def_path = YF_PATH."share/def_rewrite_rules.php";
			if (file_exists($_def_path)) {
				eval("?>".file_get_contents($_def_path));
			}
		}
		$this->_add_custom_patterns();
		// Try to use global patterns for the site
		if (isset($GLOBALS['REWRITE_PATTERNS'])) {
			$this->_replace_patterns = $GLOBALS['REWRITE_PATTERNS'];
		} else {
			trigger_error("NO REWRITING PATTERNS SPECIFIED!", E_USER_WARNING);
		}
	}

	/**
	* Allow to add custom patterns here
	*/
	function _add_custom_patterns () {
		// Custom routing for static pages (eq. for URL like /terms/ instead of /static_pages/show/terms/)
		if (main()->STATIC_PAGES_ROUTE_TOP && MAIN_TYPE_USER) {
			$user_modules		= main()->get_data("user_modules");
			$static_pages_names	= main()->get_data("static_pages_names");
			$tmp = array();
			foreach ((array)$static_pages_names as $_name) {
				// Do not override existing modules
				if (isset($user_modules[$_name])) {
					continue;
				}
				$tmp[$_name] = preg_quote($_name, "/");
			}
			if ($tmp) {
				$new_rewrite_pattern = array(
					"/object=static_pages&action=show&id=(".implode("|", $tmp).")/i" => "\$1",
				);
				$GLOBALS['REWRITE_PATTERNS'] = my_array_merge($new_rewrite_pattern, $GLOBALS['REWRITE_PATTERNS']);
			}
		}
		if (!empty(main()->HTTPS_ENABLED_FOR)) {
			$NEW_WEB_PATH = WEB_PATH;
			$REVERT_PATTERN = false;
			// Return links to the http protocol
			if (substr(WEB_PATH, 0, 8) == "https://" && !main()->USE_ONLY_HTTPS) {
				$NEW_WEB_PATH = str_replace("https://", "http://", $NEW_WEB_PATH);
				$REVERT_PATTERN = true;
			} else {
				$NEW_WEB_PATH = str_replace("http://", "https://", $NEW_WEB_PATH);
			}
			$add_pattern = array(
				"/\.\/\?(". ($REVERT_PATTERN ? "?!" : ""). implode("|", main()->HTTPS_ENABLED_FOR).")/i"
					=> $NEW_WEB_PATH."\$1",
			);
			$GLOBALS['REWRITE_PATTERNS'] = my_array_merge($add_pattern, $GLOBALS['REWRITE_PATTERNS']);
		}
		// Add project-specific patterns to the end
		if (!empty($GLOBALS['REWRITE_PROJECT_PATTERNS'])) {
			foreach ((array)$GLOBALS['REWRITE_PROJECT_PATTERNS'] as $k => $v) {
				$GLOBALS['REWRITE_PATTERNS'][$k] = $v;
			}
		}
	}

	/**
	* Replace links for mod_rewrite
	*/
	function _rewrite_replace_links ($body = "", $standalone = false, $force_rewrite = false, $for_site_id = false) {
		// Skip rewriting for the admin section
		if (MAIN_TYPE_ADMIN && !$force_rewrite) {
			return $body;
		}
		if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
			$this->_time_start = microtime(true);
		}
		// Try to get links from the output page
		$links = $standalone ? array($body) : $this->_get_unique_links($body);
		// Process links (if exists ones)
		if (!empty($links) && is_array($links) && !empty($this->_replace_patterns) && is_array($this->_replace_patterns)) {
			// Process common patterns
			$rewrited_data = preg_replace(
				array_keys($this->_replace_patterns), 
				array_values($this->_replace_patterns), 
				str_replace("object=".YF_PREFIX, "object=", $links) // Fix for cases when __CLASS__ used in links generation inside YF
			);
			// Process extension
			if (strlen($this->_rewrite_add_extension)) {
				foreach ((array)$rewrited_data as $k => $v) {
					// Add extension (checking if it was added before)
					if ($this->_rewrite_add_extension == "/") {
						$ext = $this->_rewrite_add_extension;
					} else {
						$ext = ($links[$k] != "./" && false === strpos($v, $this->_rewrite_add_extension) ? $this->_rewrite_add_extension : "");
					}
					$rewrited_data[$k] = $v. $ext;
				}
			}
			// Allow links with anchors
			$rewrited_data = preg_replace("/(\#[a-z0-9\_]+)\//i", "/\$1", $rewrited_data);
			// Replace rewrited links in the output
			$r_array = array();
			foreach ((array)$links as $k => $v) {
				$r_array[$v] = $rewrited_data[$k];
			}
			// Fix for bug with similar shorter links
			function _sort_by_length ($a, $b) {
				$sa = strlen($a);
				$sb = strlen($b);
				if ($sa == $sb) {
					return 0;
				}
				return ($sa < $sb) ? +1 : -1;
			}
			uksort($r_array, array(&$this, "_sort_by_length"));
			// DO NOT USE strtr() here!!!
			$body = str_replace(array_keys($r_array), array_values($r_array), $body);

			// Show debug info if needed
			if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
				if (empty($GLOBALS["REWRITE_DEBUG"])) $GLOBALS["REWRITE_DEBUG"] = array("SOURCE"=> array(),	"REWRITED"	=> array());
				$GLOBALS["REWRITE_DEBUG"]["SOURCE"]		= array_merge($GLOBALS["REWRITE_DEBUG"]["SOURCE"],		$links);
				$GLOBALS["REWRITE_DEBUG"]["REWRITED"]	= array_merge($GLOBALS["REWRITE_DEBUG"]["REWRITED"],	$rewrited_data);
			}
		}
		if (!empty($GLOBALS["location"])) {
			$body = $this->_replace_in_main_header($body);
		}
		// Special processing for the admin section with lot of system sites (we need to generate exact url for given site_id)
		if ($for_site_id) {
			if (!isset($GLOBALS['_SYS_SITES_CACHE'])) {
				$GLOBALS['_SYS_SITES_CACHE'] = main()->get_data("sys_sites");
			}
			$SITE = $GLOBALS['_SYS_SITES_CACHE'][$for_site_id];
			if ($SITE && $SITE["country"] && $SITE["vertical"]) {
				$country	= $SITE["country"];
				$vertical	= $SITE["vertical"];
				if (!empty($GLOBALS["DOMAINS"][$country])) {
					$_host = parse_url($body, PHP_URL_HOST);
					$body = str_replace($_host, $GLOBALS["DOMAINS"][$country], $body);
				}
				$_tmp = l("verticals", "", $country);
				$tr_vertical = $_tmp[$vertical];
				if ($tr_vertical) {
					$body = str_replace($GLOBALS["DOMAINS"][$country]."/", $GLOBALS["DOMAINS"][$country]."/".$tr_vertical."/", $body);
				}
			}
		}
		if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
			if (!isset($GLOBALS['rewrite_exec_time'])) {
				$GLOBALS['rewrite_exec_time'] = 0;
			}
			$GLOBALS['rewrite_exec_time'] += (microtime(true) - $this->_time_start);
		}
		return $body;
	}

	/**
	* Replace links to handle IFRAME src (if IFRAME_CENTER mode is enabled)
	*/
	function _replace_links_for_iframe($body = "") {
		$replace_path = MAIN_TYPE_USER ? WEB_PATH : ADMIN_WEB_PATH;
		$unique_links = $this->_get_unique_links($body, true);
		if (!empty($unique_links) && is_array($unique_links)) {
			$body = str_replace("./?", $replace_path."?", $body);
			foreach ((array)$unique_links as $v) {
				$replace_pairs[$replace_path."?".$v."\""] = $replace_path."?".$v."&center_area=1\" target=\"iframe_center\"";
			}
			if (count($replace_pairs)) {
				$body = str_replace(array_keys($replace_pairs), array_values($replace_pairs), $body);
			}
		}
		return $body;
	}

	/**
	* Get all ProEngine links
	*/
	function _get_unique_links ($text = "", $for_iframe = false) {
		$unique = array();
		preg_match_all($for_iframe ? $this->_iframe_pattern : $this->_links_pattern, $text, $matches);
		foreach ((array)$matches['2'] as $k => $v) {
			if (strlen($v) && !in_array($v, $unique)) {
				$unique[] = $v;
			}
		}
		return $unique;
	}

	/**
	* Replace {location}
	*/
	function _replace_in_main_header ($body = "") {
		$location = $GLOBALS['location'];
		// Text for replace
		if (defined("SITE_KW_HEAD_TEXT")) {
			$base_text = SITE_KW_HEAD_TEXT;
		} else {
			$base_text = "Looking for {location} escort? {location} escorts are all here!";
		}
		$text_prefix = "<h2 class=\"kw_head\">";
		$text_postfix = "</h2>";
		$replaced_text = str_replace(array("LOCATION", "{location}"), _ucfirst(str_replace("_", " ", $location)), $base_text);
		// Replace header inside it
		if (!empty($location)) {
			$body = str_replace($text_prefix.$text_postfix, $text_prefix._prepare_html($replaced_text).$text_postfix, $body);
		}
		return $body;
	}
	
	/**
	*/
    function _force_get_url ($params = array(), $host = "", $url_str = "", $gen_cache = true) {
		$time_start = microtime(true);
		if(!is_array($params) && empty($url_str)){
			return false;
		}
		if((isset($_GET["debug"]) && $_GET["debug"] == 57) || isset($_GET["no_cache"]) || isset($_GET["no_core_cache"])){
			$params['debug'] = (isset($_GET["debug"]) && $_GET["debug"] == 57) ? 57 : "";
			$params['no_cache'] = isset($_GET["no_cache"]) ? "y" : "";
			$params['no_core_cache'] = isset($_GET["no_core_cache"]) ? "y" : "";
		}

		if(empty($url_str)){
			if(isset($params["action"]) && empty($params["action"])){
				$params["action"] = "show";
			}
		}
		foreach((array)$params as $k => $v){
			if(empty($v)){
				unset($params[$k]);
				continue;
			}
		}
		// patterns support here
		$params['host'] = !empty($host) ? $host : $_SERVER["HTTP_HOST"];
		if ($GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] == 1) {
			$link = $this->REWRITE_PATTERNS['yf']->_get($params);
		} else {
			foreach ((array)$params as $k => $v) {
				if ($k == 'host') {
					continue;
				}
				$arr_out[] = $k."=".$v;
			}
			if (!empty($arr_out)) {
				$u .= "?".implode("&",$arr_out);
			}
			$link = $this->_correct_protocol("http://{$params[host]}/{$u}");
		}
		return $link;
    }

    /**
	*
    */
    function _correct_protocol($url) {
        if (empty($url)) {
            return false;
        }
        if (empty(main()->HTTPS_ENABLED_FOR)) {
            return $url;
        }
        // Return links to the http protocol
        if (substr($url, 0, 8) == "https://" && !main()->USE_ONLY_HTTPS) {
            $url = str_replace("https://", "http://", $url);
        } else {
            $url = str_replace("http://", "https://", $url);
        }
        return $url;
    }
}
