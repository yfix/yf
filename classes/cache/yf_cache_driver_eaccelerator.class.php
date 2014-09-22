<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_eaccelerator extends yf_cache_driver {

	/**
	*/
	function is_ready() {
		return function_exists('eaccelerator_get');
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		$result = eaccelerator_get($name);
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
		return eaccelerator_put($name, $data, $ttl);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return eaccelerator_rm($key_name_ns);
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return eaccelerator_clear();
	}

	/**
	*/
	function stats() {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
		return array();
	}
}
