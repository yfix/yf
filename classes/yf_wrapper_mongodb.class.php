<?php

/**
* MongoDB API wrapper
*/
class yf_wrapper_mongodb {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->host = getenv('MONGODB_HOST') ?: conf('MONGODB_HOST') ?: '127.0.0.1';
		$this->port = getenv('MONGODB_PORT') ?: conf('MONGODB_PORT') ?: 27017;
	}

	/**
	*/
	function is_ready() {
// TODO
	}

	/**
	*/
	function connect($params = array()) {
// TODO
	}

	/**
	*/
	function get($key) {
		return $this->connection->get($key);
	}

	/**
	*/
	function set($key, $val) {
		return $this->connection->set($key, $val);
	}
}
