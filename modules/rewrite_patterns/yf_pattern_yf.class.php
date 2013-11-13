<?php

/**
* Default YF rewrite pattern
*/
class yf_pattern_yf {
	function _get($a) {
		if ($a['task'] == 'login' || $a['task'] == 'logout') {
			$u = $a['task'];
			unset($a['task']);
		} else {
			$u = array();
			if (!empty($a['object'])) {
				$u[] = $a['object'];
				if (empty($a['action'])) {
					$a['action'] = 'show';
				}
				// Make urls shorter if default action found.
				if ($a['action'] != 'show') {
					$u[] = $a['action'];
				}
				if (!empty($a['id'])) {
					$u[] = $a['id'];
				}
			}
			$u = implode('/',$u);
		}
		$arr = $a;
		$arr_out = array();
		unset($arr['object']);
		unset($arr['action']);
		unset($arr['host']);
		unset($arr['id']);
		foreach ((array)$arr as $k => $v) {
			$arr_out[] = $k.'='.$v;
		}
		if (!empty($u)) {
#			$u .= '.html';
		}
		if (!empty($arr_out)) {
			$u .= '?'.implode('&',$arr_out);
		}
#		return module('rewrite')->_correct_protocol('http://'.$a['host'].'/'.$u);
		return module('rewrite')->_correct_protocol(WEB_PATH. $u);
	}

	/**
	*/
	function _parse ($host, $url, $query) {
		$s = '';
		// Examples: /login    /logout
		if ($url[0] == 'login' || $url[0] == 'logout') {
			$s = 'task='.$url[0];
		// Examples: /user_profile/5
		} elseif (!empty($url[0]) && !empty($url[1]) && is_numeric($url[1])) {
			$s = 'object='.$url[0].'&action=show';
			$url[2] = $url[1]; // id
		// Examples: /test/oauth
		} elseif (!empty($url[0]) && !empty($url[1])) {
			$s = 'object='.$url[0].'&action='.$url[1];
		// Examples: /test/  /test
		} elseif (!empty($url[0])) {
			$s = 'object='.$url[0].'&action=show';
		// Examples: define('SITE_DEFAULT_PAGE', './?object=index&action=some_action')
		} elseif (defined('SITE_DEFAULT_PAGE')) {
			$s = ltrim(SITE_DEFAULT_PAGE, './');
		// Default inner url
		} else {
			$s = 'object=home_page&action=show';
		}
		if (!empty($url[2])) {
			$s .= '&id='.$url[2];
		}
		if ($s != '') {
			parse_str($s, $arr);
			foreach ((array)$query as $k => $v) {
				$arr[$k] = $v;
			}
		}
		// Filter bad symbols
		$arr['object'] = preg_replace('~[^a-z0-9_]+~ims', '', trim($arr['object']));
		$arr['action'] = preg_replace('~[^a-z0-9_]+~ims', '', trim($arr['action']));
		return $arr;
	}
}
