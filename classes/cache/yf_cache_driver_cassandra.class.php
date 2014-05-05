<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_cassandra extends yf_cache_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_connected) && method_exists($this->_connected, $name)) {
			return call_user_func_array(array($this->_connected, $name), $args);
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function is_ready() {
// TODO
		return false;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}
}
