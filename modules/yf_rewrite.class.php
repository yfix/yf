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
				parse_str($url['query'], $arr);
				if (MAIN_TYPE_ADMIN && in_array($arr['task'], array('login','logout'))) {
					continue;
				}
				$replace = $this->_force_get_url($arr, $_SERVER['HTTP_HOST']);
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
			$body = str_replace(array_keys($r_array), array_values($r_array), $body);
			if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
				$exec_time = (microtime(true) - $this->_time_start);
				$trace = main()->trace_string();
				foreach ((array)$r_array as $s => $r) {
					debug('rewrite[]', array(
						'source'	=> $s,
						'rewrited'	=> $r,
						'trace'		=> $trace,
						'exec_time'	=> $exec_time,
					));
				}
			}
		}
		if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
			debug('rewrite_exec_time', debug('rewrite_exec_time') + $exec_time);
		}
		return $body;
	}

	/**
	*/
	function _force_get_url ($params = array(), $host = '', $url_str = '', $gen_cache = true, $for_section = false) {
		if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
			$time_start = microtime(true);
		}
		if (!is_array($params) && is_string($params)) {
			$url_str = trim($params);
			$orig_url_str = $url_str;
			$params = array();
			if (preg_match('~[a-z0-9_\./]+~ims', $url_str)) {
				if ($url_str[0] == '/') {
					// Example: /test/oauth/github => object=test, action=oauth, id=github
					list(,$params['object'], $params['action'], $params['id'], $params['page']/*, $params['other']*/) = explode('/', $url_str);
				} else {
					// Example: test2.dev/test/oauth/github => host=test2.dev, object=test, action=oauth, id=github
					list($params['host'], $params['object'], $params['action'], $params['id'], $params['page']/*, $params['other']*/) = explode('/', $url_str);
				}
			}
			if (is_array($host)) {
				$params += (array)$host;
				$host = $params['host'];
			}
		}
		if (!is_array($params) && empty($url_str)) {
			return false;
		}
		$params = array_reverse($params);
		if (isset($_GET['debug']) || isset($_GET['no_cache']) || isset($_GET['no_core_cache'])) {
			$params['debug'] = $_GET['debug'];
			$params['no_cache'] = isset($_GET['no_cache']) ? 'y' : '';
			$params['no_core_cache'] = isset($_GET['no_core_cache']) ? 'y' : '';
		}
		if (empty($url_str)) {
			if (isset($params['action']) && empty($params['action'])) {
				$params['action'] = 'show';
			}
		}
		if ($params['object'] == '@object') {
			$params['object'] = $_GET['object'];
		}
		if ($params['action'] == '@action') {
			$params['action'] = $_GET['action'];
		}
		if ($params['id'] == '@id') {
			$params['id'] = $_GET['id'];
		}
		if ($params['page'] == '@page') {
			$params['page'] = $_GET['page'];
		}
		foreach ((array)$params as $k => $v) {
			if (empty($v) && ($v !== '0')) {
				unset($params[$k]);
				continue;
			}
		}
		// patterns support here
		if (!isset($params['host'])) {
			$params['host'] = !empty($host) ? $host : $_SERVER['HTTP_HOST'];
		}
		$REWRITE_ENABLED = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
		if ($REWRITE_ENABLED && $for_section != 'admin') {
			$link = $this->REWRITE_PATTERNS['yf']->_get($params);
		} else {
			foreach ((array)$params as $k => $v) {
				if ($k == 'host') {
					continue;
				}
				$arr_out[] = $k.'='.$v;
			}
			if (!empty($arr_out)) {
				$u .= '?'.implode('&', $arr_out);
			}
			if ($for_section == 'admin') {
				$link = ADMIN_WEB_PATH. $u;
			} else {
				$link = $this->_correct_protocol('http://'.$params['host'].'/'.$u);
			}
		}
        if (DEBUG_MODE) {
			debug(__FUNCTION__.'[]', array(
				'params'		=> $params,
				'rewrited_link' => $link,
				'host'			=> $host,
				'url_str'		=> $url_str,
				'time'			=> microtime(true) - $time_start,
				'trace' 		=> main()->trace_string(),
			));
		}
		return $link;
	}

	/**
	*/
	function _correct_protocol($url) {
		if (empty($url)) {
			return false;
		}
		if (empty(main()->HTTPS_ENABLED_FOR)) {
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

	/**
	*/
	function _process_url ($url = '', $force_rewrite = false, $for_site_id = false) {
		if (tpl()->REWRITE_MODE) {
			$url = $this->_rewrite_replace_links($url, true, $force_rewrite, $for_site_id);
		} elseif (substr($url, 0, 3) == './?') {
			$url = WEB_PATH. substr($url, 2);
		}
		// fix for rewrite tests
		return str_replace(array('http:///', 'https:///'), './', $url);
	}

	/**
	*/
	function _url ($params = array(), $host = '', $url_str = '') {
		return $this->_force_get_url($params, $host, $url_str);
	}

	/**
	* Generate url for admin section, no matter from where was called
	*/
	function _url_admin ($params = array(), $host = '', $url_str = '') {
		return $this->_force_get_url($params, $host, $url_str, true, $for_section = 'admin');
	}

	/**
	* Generate url for user section, no matter from where was called
	*/
	function _url_user ($params = array(), $host = '', $url_str = '') {
		return $this->_force_get_url($params, $host, $url_str, true, $for_section = 'user');
	}

	/**
	*/
	function _generate_url($params = array(), $host = '') {
// TODO
	}
}
