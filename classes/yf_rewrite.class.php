<?php

class yf_rewrite {

	public $DEFAULT_HOST = '';
	public $DEFAULT_PORT = '';
	public $special_links = array('../', './', '/');
	public $URL_ADD_BUILTIN_PARAMS = true;
	public $PARSE_RULES = array();
	public $BUILD_RULES = array();
	public $allowed_url_params = array(
		'utm_source',
		'utm_medium',
		'utm_content',
		'utm_campaign',
		'utm_term',
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* YF module constructor
	*/
	function _init () {
		$this->REWRITE_PATTERNS = array(
			'yf' => _class('rewrite_pattern_yf', 'classes/rewrite/'),
		);
		if (!$this->DEFAULT_HOST) {
			if (defined('WEB_DOMAIN') && strlen(WEB_DOMAIN)) {
				$this->DEFAULT_HOST = WEB_DOMAIN;
			}
			if (!$this->DEFAULT_HOST && defined('WEB_PATH')) {
				$host = parse_url(WEB_PATH, PHP_URL_HOST);
				if ($host && !(main()->web_path_was_not_defined && $host === '127.0.0.1')) {
					$this->DEFAULT_HOST = $host;
				}
			}
			if (!$this->DEFAULT_HOST && $_SERVER['HTTP_HOST']) {
				$this->DEFAULT_HOST = $_SERVER['HTTP_HOST'];
			}
		}
		if (!$this->DEFAULT_PORT) {
			if (defined('WEB_PATH') && strlen(WEB_PATH)) {
				$port = parse_url(WEB_PATH, PHP_URL_PORT);
				if ($port && $port != '80') {
					$this->DEFAULT_PORT = $port;
				}
			}
			if (!$this->DEFAULT_PORT && $_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != '80') {
				$this->DEFAULT_PORT = $_SERVER['SERVER_PORT'];
			}
			if (!$this->DEFAULT_PORT) {
				$this->DEFAULT_PORT = '80';
			}
		}
	}

	/**
	* Replace links for url rewrite
	*/
	function _rewrite_replace_links ($body = '', $standalone = false, $force_rewrite = false, $for_site_id = false) {
		if (MAIN_TYPE_ADMIN && !$force_rewrite) {
			return $body;
		}
		if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
			$trace = main()->trace_string();
			$this->_time_start = microtime(true);
		}
		// Special processing for short links '/', './', '../' == this case mostly used in redirects like js_redirect('./')
		if (in_array(trim($body), $this->special_links)) {
			$out = $this->_url('/');
			if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
				debug('rewrite[]', array(
					'source'	=> $body,
					'rewrited'	=> $out,
					'trace'		=> $trace,
					'exec_time'	=> (microtime(true) - $this->_time_start),
				));
			}
			return $out;
		}
		$links = $standalone ? array($body) : $this->_get_unique_links($body);
		if (!empty($links) && is_array($links)) {
			$r_array = array();
			$has_special = false;
			foreach ($links as $v) {
				if (in_array($v, $this->special_links)) {
					$has_special = true;
					continue;
				}
				$url = parse_url($v);
				parse_str($url['query'], $arr);
				if (MAIN_TYPE_ADMIN && in_array($arr['task'], array('login','logout'))) {
					continue;
				}
				$replace = $this->_url($arr). (strlen($url['fragment']) ? '#'.$url['fragment'] : '');
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
			if ($has_special) {
				$body = $this->_replace_special_links($body, $links);
			}
			if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
				foreach ((array)$r_array as $s => $r) {
					debug('rewrite[]', array(
						'source'	=> $s,
						'rewrited'	=> $r,
						'trace'		=> $trace,
						'exec_time'	=> (microtime(true) - $this->_time_start),
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
	* Special processing for short links '/', './', '../'
	*/
	function _replace_special_links ($body = '', $links = array()) {
		$rewrite_to_url = $this->_url('/');
		foreach ((array)$this->special_links as $link) {
			if (!in_array($link, $links)) {
#				continue;
			}
			$regex = '~(?P<part1>(href|src)\s*=\s*["\']{1})\s*'.preg_quote($link, '~').'?\s*(?P<part2>["\']{1}[\s>]?)~ims';
			$replace_into = '\1'.$rewrite_to_url.'\3';
			$body = preg_replace($regex, $replace_into, $body);
		}
		return $body;
	}

	/**
	*/
	function _is_our_url ($url) {
		$result = parse_url($url);
		$host = $result['host'];
		$u = preg_replace('/\.htm.*/', '', $result['path']);
		$u = trim($u, '/');
		$u_arr = explode('/', $u);
		parse_str($result['query'], $s_arr);

		$arr = $this->REWRITE_PATTERNS['yf']->_parse($host, (array)$u_arr, (array)$s_arr, $url, $this);

		$new_url = $this->_url($arr, WEB_DOMAIN);

		return $url == $new_url;
	}

	/**
	*/
	function _process_url ($url = '', $force_rewrite = false, $for_site_id = false) {
		if (strpos($url, 'http://') === false && strpos($url, 'https://') !== 0) {
			$url = $this->_rewrite_replace_links($url, true, $force_rewrite, $for_site_id);
		}
		// fix for rewrite tests
		return str_replace(array('http:///', 'https:///'), './', $url);
	}

	/**
	* Generate url for admin section, no matter from where was called
	*/
	function _url_admin ($params = array(), $host = '', $url_str = '') {
		return $this->_url($params, $host, $url_str, $for_section = 'admin');
	}

	/**
	* Generate url for user section, no matter from where was called
	*/
	function _url_user ($params = array(), $host = '', $url_str = '') {
		return $this->_url($params, $host, $url_str, $for_section = 'user');
	}

	/**
	*/
	function _url ($params = array(), $host = '', $url_str = '', $for_section = null) {
		if (DEBUG_MODE && !$this->FORCE_NO_DEBUG) {
			$time_start = microtime(true);
		}
		if (!is_array($params) && is_string($params)) {
			$url_str = trim($params);
			$orig_url_str = $url_str;
			$params = array();
			$params['fragment'] = parse_url($url_str, PHP_URL_FRAGMENT);
			if (strlen($params['fragment'])) {
				$url_str = str_replace('#'.$params['fragment'], '', $url_str);
			}
			if (preg_match('~[a-z0-9_\./]+~ims', $url_str)) {
				$_other = '';
				if (strpos($url_str, '?') !== false) {
					list($url_str, $_other) = explode('?', $url_str);
				}
				// Example: ./test/oauth/github, ../test/oauth/github
				if ($url_str[0] == '.') {
					$url_str = ltrim($url_str, '.');
				}
				if ($url_str[0] == '/') {
					if ($url_str[1] == '/') {
						// Example: //test/test_action/&k1=v1&k2=v2 => object=test, action=test_action, k1=v1, k2=v2
						list(,,$params['object'], $params['action'], $params['_other']) = explode('/', $url_str);
					} else {
						// Example: /test/oauth/github => object=test, action=oauth, id=github
						list(,$params['object'], $params['action'], $params['id'], $params['page'], $params['_other']) = explode('/', $url_str);
					}
				} else {
					// Example: test2.dev/test/oauth/github => host=test2.dev, object=test, action=oauth, id=github
					list($params['host'], $params['object'], $params['action'], $params['id'], $params['page'], $params['_other']) = explode('/', $url_str);
				}
				if (!$params['_other'] && $_other) {
					$params['_other'] = $_other;
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
		if (!$for_section || ($for_section !== 'user' && $for_section !== 'admin')) {
			$for_section = MAIN_TYPE;
		}
		// Support for other params passed by http encoded string (&k1=v1&k2=v2)
		if (isset($params['_other'])) {
			parse_str(trim($params['_other'], '&?'), $tmp);
			foreach ((array)$tmp as $k => $v) {
				$k = trim($k);
				$v = trim($v);
				if (strlen($k) && strlen($v)) {
					$params[$k] = $v;
				}
			}
			unset($params['_other']);
		}
		// Ensure correct order of params
		$p = array();
		foreach (array('object','action','id','page') as $name) {
			if (isset($params[$name])) {
				$p[$name] = $params[$name];
			}
		}
		foreach ((array)$params as $k => $v) {
			$p[$k] = $v;
		}
		$params = $p;
		unset($p);
		// Add built-in url params, if needed
		if ($this->URL_ADD_BUILTIN_PARAMS && (isset($_GET['debug']) || isset($_GET['no_cache']) || isset($_GET['no_core_cache']) || isset($_GET['host']))) {
			$params['debug'] = $_GET['debug'];
			$params['get_host'] = $_GET['host'];
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
		if (empty($params['host'])) {
			$params['host'] = !empty($host) ? $host : $this->DEFAULT_HOST;
		}
		if (empty($params['port'])) {
			$port = $port ?: $this->DEFAULT_PORT;
			if ($port != '80') {
				$params['port'] = $port;
			}
		}
		$REWRITE_ENABLED = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
		if ($REWRITE_ENABLED && $for_section != 'admin') {
			$link = $this->REWRITE_PATTERNS['yf']->_build($params, $this);
		} else {
			$skip_url_params = array('host', 'port', 'fragment', 'path', 'admin_host', 'admin_port', 'admin_path');
			foreach ((array)$params as $k => $v) {
				if (in_array($k, $skip_url_params)) {
					continue;
				}
				$arr_out[] = $k.'='.$v;
			}
			if (!empty($arr_out)) {
				$u .= (strpos($u, '?') === false ? '?' : '&'). implode('&', $arr_out);
			}
			$http_protocol = main()->USE_ONLY_HTTPS ? 'https' : 'http';
			if ($for_section == 'admin') {
				if ($params['admin_host']) {
					$_host = $params['admin_host'];
					$_port = $params['admin_port'] ?: '80';
					$_path = $params['admin_path'] ?: '/admin/';
					$link = $this->_correct_protocol($http_protocol. '://'. $_host. ($_port && $_port != '80' ? ':'.$_port : ''). ($_path ?: '/'). $u);
				} else {
					$link = ADMIN_WEB_PATH. $u;
				}
			} else {
				$_host = $params['host'];
				$_port = $params['port'] ?: '80';
				$_path = $params['path'] ?: '/';
				$link = $this->_correct_protocol($http_protocol. '://'. $_host. ($_port && $_port != '80' ? ':'.$_port : ''). ($_path ?: '/'). $u);
			}
			if ($params['fragment']) {
				$link .= '#'.$params['fragment'];
			}
		}
        if (DEBUG_MODE) {
			debug(__FUNCTION__.'[]', array(
				'params'		=> $params,
				'rewrited_link' => $link,
				'host'			=> $params['host'],
				'port'			=> $params['port'],
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
		if (!strlen($url)) {
			return false;
		}
		$main = main();
		$is_http = false;
		$is_https = false;
		$change_to_http = false;
		$change_to_https = false;
		$matched = false;
		if ($main->HTTPS_ENABLED_FOR) {
			foreach ((array)$main->HTTPS_ENABLED_FOR as $item) {
				if (is_callable($item)) {
					if ($item($url)) {
						$matched = true;
						break;
					}
				} elseif (preg_match('@'.$item.'@ims', $url)) {
					$matched = true;
					break;
				}
			}
		}
		// Return links to the http protocol
		if (substr($url, 0, 2) == '//') {
			$url = str_replace('//', 'http://', $url);
		}
		if (substr($url, 0, 8) == 'https://') {
			$is_https = true;
			if ($main->HTTPS_ENABLED_FOR) {
				if (!$matched) {
					$change_to_http = true;
				}
			} elseif (!$main->USE_ONLY_HTTPS) {
				$change_to_http = true;
			}
		} elseif (substr($url, 0, 7) == 'http://') {
			$is_http = true;
			if ($main->USE_ONLY_HTTPS) {
				$change_to_https = true;
			} elseif ($main->HTTPS_ENABLED_FOR) {
				if ($matched) {
					$change_to_https = true;
				}
			}
			if ($https_needed) {
				$url = str_replace('http://', 'https://', $url);
			}
		}
		if ($is_http && $change_to_https) {
			$url = str_replace('http://', 'https://', $url);
		} elseif ($is_https && $change_to_http) {
			$url = str_replace('https://', 'http://', $url);
		}
		return $url;
	}

	/**
	*/
	function _get_unique_links ($text = '', $for_iframe = false) {
		$unique = array();
		$pattern = '/(action|location|href|src)[\s]{0,1}=[\s]{0,1}["\']?(\.\/\?[^"\'\>\s]+|\.\/)["\']?/ims';
		preg_match_all($pattern, $text, $matches);
		foreach ((array)$matches['2'] as $k => $v) {
			if (strlen($v) && !in_array($v, $unique)) {
				$unique[] = $v;
			}
		}
		return $unique;
	}
}
