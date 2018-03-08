<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_tmp extends yf_cache_driver {

	public $storage = [];
	protected $hits = 0;
	protected $misses = 0;

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
		$this->storage = [];
	}

	/**
	*/
	function is_ready() {
		return true;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = []) {
		if (isset($this->storage[$name])) {
			$this->_hits++;
		} else {
			$this->_misses++;
		}
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
		$this->storage = [];
		return true;
	}

	/**
	*/
	function list_keys() {
		return array_keys($this->storage);
	}

	/**
	*/
	function stats() {
		return [
			'hits'		=> $this->hits,
			'misses'	=> $this->misses,
			'uptime'	=> null,
			'mem_usage'	=> memory_get_usage(),
			'mem_avail'	=> null,
		];
	}
}
