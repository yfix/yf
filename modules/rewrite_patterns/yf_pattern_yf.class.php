<?php

/**
* Default YF rewrite pattern
*/
class yf_pattern_yf {
	function _get($A) {
		if ($A['task'] == 'login' || $A['task'] == 'logout') {
			$u = $A['task'];
			unset($A['task']);
		} else{ 
			$u = array();
			if (!empty($A['object'])) {
				$u[] = "{$A['object']}";
				if (empty($A['action'])) {
					$A['action'] = 'show';
				}
				$u[] = "{$A['action']}";
				if (!empty($A['id'])) {
					$u[] = "{$A['id']}";
				}
			}
			$u = implode("/",$u);
		}
		$arr = $A;
		$arr_out = array();
		unset($arr['object']);
		unset($arr['action']);
		unset($arr['host']);
		unset($arr['id']);
		foreach((array)$arr as $k => $v) {
			$arr_out[] = $k."=".$v;
		}
		if (!empty($u)) {
			$u .= ".html";
		}
		if (!empty($arr_out)) {
			$u .= "?".implode("&",$arr_out);
		}
		return module('rewrite')->_correct_protocol("http://{$A[host]}/{$u}");
	}

	/**
	*/
	function _parse ($host, $url, $query) {
		$s = "";
		if ($url[0] == 'login' || $url[0] == 'logout') {
			$s = "task={$url[0]}";
		} elseif (!empty($url[0]) && !empty($url[1])) {
			$s= "object={$url[0]}&action={$url[1]}";
		} elseif (!empty($url[0])) {
			$s= "object={$url[0]}&action=show";			
		} else {
			$s = "object=home_page&action=show";
		}
		if (!empty($url[2])) {
			$s .= "&id={$url[2]}";
		}
		if ($s != '') {
			parse_str($s, $arr);
			foreach ((array)$query as $k => $v) {
				$arr[$k] = $v;
			}
		}
		return $arr;
	}

}
