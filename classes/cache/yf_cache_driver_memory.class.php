<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_memory extends yf_cache_driver {

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
		return $this->storage;
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
		foreach ($data as $name => $_data) {
			$this->storage[$name] = $_data;
		}
		return true;
	}

	/**
	*/
	function multi_del(array $names) {
		foreach ($names as $name) {
			if (!isset($this->storage[$name])) {
				continue;
			}
			unset($this->storage[$name]);
		}
		return true;
	}
}
