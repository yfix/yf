<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_tmp extends yf_cache_driver {

	public $storage = array();

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function __clone() {
		$this->storage = array();
	}

	/**
	*/
	function is_ready() {
		return true;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		return $this->storage[$name];
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		$this->storage[$name] = $data;
		return true;
	}

	/**
	*/
	function del($name) {
		unset($this->storage[$name]);
		return true;
	}

	/**
	*/
	function flush() {
		$this->storage = array();
		return true;
	}

	/**
	*/
	function list_keys() {
		return array_keys($this->storage);
	}

	/**
	*/
	function multi_get(array $names, $ttl = 0, $params = array()) {
		$result = array();
		foreach ($names as $name) {
			if (!isset($this->storage[$name])) {
				continue;
			}
			$result[$name] = $this->storage[$name];
		}
		return $result;
	}

	/**
	*/
	function multi_set(array $data, $ttl = 0) {
		$result = array();
		foreach ($data as $name => $_data) {
			$this->storage[$name] = $_data;
			$result[$name] = true;
		}
		return $result;
	}

	/**
	*/
	function multi_del(array $names) {
		$result = array();
		foreach ($names as $name) {
			if (isset($this->storage[$name])) {
				unset($this->storage[$name]);
				$result[$name] = true;
			} else {
				$result[$name] = null;
			}
		}
		return $result;
	}
}
