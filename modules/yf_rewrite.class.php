<?php

class yf_rewrite {
	
	/** @var string @conf_skip Links pattern */
	var	$_links_pattern			= '/(action|location|href|src)[\s]{0,1}=[\s]{0,1}["\']?(\.\/\?[^"\'\>\s]+|\.\/)["\']?/ims';
	/** @var string @conf_skip Pattern for iframe links */
	var	$_iframe_pattern		= '/(action|location|href)[\s]{0,1}=[\s]{0,1}["\']+\.\/\?([^"\'>\s]*)["\']+/ims';
	
	/**
	* YF module constructor
	*/
	function _init () {
		$this->REWRITE_PATTERNS = array('yf' => _class('pattern_yf', 'modules/rewrite_patterns/'));
	}

	/**
	* Replace links for url rewrite
	*/
	function _rewrite_replace_links ($body = '', $standalone = false, $force_rewrite = false, $for_site_id = false) {
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
		if (!empty($links) && is_array($links)) {
			$r_array = array();
			foreach ($links as $v) {
				$url = parse_url($v);
				parse_str($url['query'],$arr);
				$replace = $this->_force_get_url($arr,$_SERVER['HTTP_HOST']);
				$r_array[$v] = $replace;
			}
			// Fix for bug with similar shorter links, sort by length DESC
			uksort($r_array, function ($a, $b) {
				$sa = strlen($a);
				$sb = strlen($b);
				if ($sa == $sb) {
					return 0;
				}
				return ($sa < $sb) ? +1 : -1;
			});
			// DO NOT USE strtr() here!!!
			$body = str_replace(array_keys($r_array), array_values($r_array), $body);
			// Show debug info if needed
			if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
				if (empty($GLOBALS['REWRITE_DEBUG'])) $GLOBALS['REWRITE_DEBUG'] = array('SOURCE'=> array(),	'REWRITED'	=> array());
				$GLOBALS['REWRITE_DEBUG']['SOURCE']		= array_merge($GLOBALS['REWRITE_DEBUG']['SOURCE'],		array_keys($r_array));
				$GLOBALS['REWRITE_DEBUG']['REWRITED']	= array_merge($GLOBALS['REWRITE_DEBUG']['REWRITED'],	array_values($r_array));
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
	*/
	function _force_get_url ($params = array(), $host = '', $url_str = '', $gen_cache = true) {
		$time_start = microtime(true);
		if (!is_array($params) && empty($url_str)){
			return false;
		}
		if (isset($_GET['debug']) || isset($_GET['no_cache']) || isset($_GET['no_core_cache'])){
			$params['debug'] = $_GET['debug'];
			$params['no_cache'] = isset($_GET['no_cache']) ? 'y' : '';
			$params['no_core_cache'] = isset($_GET['no_core_cache']) ? 'y' : '';
		}

		if (empty($url_str)){
			if (isset($params['action']) && empty($params['action'])){
				$params['action'] = 'show';
			}
		}
		foreach ((array)$params as $k => $v){
			if (empty($v) && ($v !== '0')){
				unset($params[$k]);
				continue;
			}
		}
		// patterns support here
		$params['host'] = !empty($host) ? $host : $_SERVER['HTTP_HOST'];
		if ($GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] == 1){
			$link = $this->REWRITE_PATTERNS['yf']->_get($params);
		} else {
			foreach ((array)$params as $k=>$v) {
				if ($k == 'host') continue;
				$arr_out[] = $k.'='.$v;
			}
			if (!empty($arr_out)) $u .= '?'.implode('&',$arr_out);
			$link = $this->_correct_protocol('http://{$params[host]}/{$u}');
		}
		return $link;
	}

	/**
	*/
	function _correct_protocol($url){
		if (empty($url)){
			return false;
		}
		if (empty(main()->HTTPS_ENABLED_FOR)){
			return $url;
		}
		// Return links to the http protocol
		if (substr($url, 0, 8) == 'https://' && !main()->USE_ONLY_HTTPS) {
			$url = str_replace('https://', 'http://', $url);
		} else {
			$url = str_replace('http://', 'https://', $url);
		}
		return $url;
	}

	/**
	*/
	function _get_unique_links ($text = '', $for_iframe = false) {
		$unique = array();
		preg_match_all($for_iframe ? $this->_iframe_pattern : $this->_links_pattern, $text, $matches);
		foreach ((array)$matches['2'] as $k => $v) {
			if (strlen($v) && !in_array($v, $unique)) {
				$unique[] = $v;
			}
		}
		return $unique;
	}
}
