<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_riak extends yf_cache_driver {
	function is_ready() {
// TODO
		return false;
	}
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}
}
