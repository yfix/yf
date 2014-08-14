<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_xcache extends yf_cache_driver {

	/**
	*/
	function _init() {
#		ini_set('xcache.admin.user', 'yf_xcache_admin');
#		ini_set('xcache.admin.pass', md5('yf_xcache_pass'));
	}

	/**
	*/
	function is_ready() {
		return function_exists('xcache_get') && ini_get('xcache.cacher');
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		$result = xcache_get($name);
		if (is_string($result)) {
			$try_unpack = unserialize($result);
			if ($try_unpack || substr($result, 0, 2) == 'a:') {
				$result = $try_unpack;
			}
		}
		return $result;
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		return xcache_set($name, $data, $ttl);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return xcache_unset($name);
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
// Not available without admin settings
		return xcache_clear_cache();
	}
}
