<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_db extends yf_cache_driver {
// TODO
// NOTE: I plan to use Engine=memory for this type of caching
	function get($name, $ttl = 0, $params = array()) {
	}
	function set($name, $data, $ttl = 0) {
	}
	function del($name) {
	}
	function clean($name) {
	}
}
