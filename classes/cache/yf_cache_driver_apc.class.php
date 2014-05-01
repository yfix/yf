<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_apc extends yf_cache_driver {
// TODO
	function get($name, $ttl = 0, $params = array()) {
		$result = apc_fetch($name);
		if (is_string($result)) {
			$try_unpack = unserialize($result);
			if ($try_unpack || substr($result, 0, 2) == 'a:') {
				$result = $try_unpack;
			}
		}
		return $result;
	}
	function set($name, $data, $ttl = 0) {
		return apc_store($name, $data, $ttl);
	}
	function del($name) {
	}
	function clear_all() {
		return apc_clear_cache();
	}
}
