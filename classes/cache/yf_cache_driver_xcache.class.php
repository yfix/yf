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
			if ($result === 'false') {
				$result = false;
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
		if ($data === false) {
			$data = 'false';
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
		if ($this->_check_xcache_auth()) {
			xcache_clear_cache(XC_TYPE_VAR, 0);
			return true;
		}
		return null;
	}

	/**
	*/
	function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		if (!$this->_check_xcache_auth()) {
			return null;
		}
		$info = xcache_info(XC_TYPE_VAR, 0);
		return array(
			'hits'		=> $info['hits'],
			'misses'	=> $info['misses'],
			'uptime'	=> null,
			'mem_usage'	=> $info['size'],
			'mem_avail'	=> $info['avail'],
		);
	}

	/**
	*/
	protected function _check_xcache_auth() {
		if (ini_get('xcache.admin.enable_auth')) {
			throw new Exception('To use all features of Xcache cache, you must set "xcache.admin.enable_auth" to "Off" in your php.ini.');
			return null;
		}
		return true;
	}
}
