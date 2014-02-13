<?php

load('cache_driver', 'framework', 'classes/session/');
class yf_cache_driver_memcached extends yf_cache_driver {
// TODO
	function get($name, $ttl = 0, $params = array()) {
	}
	function set($name, $data, $ttl = 0) {
	}
	function del($name) {
	}
	function multi_get(array $names, $ttl = 0, $params = array()) {
	}
	function multi_set(array $data, $ttl = 0) {
	}
	function multi_del(array $names) {
	}
	function clean($name) {
	}
}
