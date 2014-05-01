<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_eaccelerator extends yf_cache_driver {
// TODO
	function get($name, $ttl = 0, $params = array()) {
		$result = eaccelerator_get($name);
		if (is_string($result)) {
			$try_unpack = unserialize($result);
			if ($try_unpack || substr($result, 0, 2) == 'a:') {
				$result = $try_unpack;
			}
		}
		return $result;
	}
	function set($name, $data, $ttl = 0) {
		return eaccelerator_put($name, $data, $ttl);
	}
	function del($name) {
	}
	function clean_all() {
		return eaccelerator_clear();
	}
}
