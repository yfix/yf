<?php

/**
* CouchBase API wrapper
*/
class yf_wrapper_couchbase {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			return call_user_func_array(array($this->_connection, $name), $args);
		}
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->host = getenv('COUCHBASE_HOST') ?: conf('COUCHBASE_HOST') ?: '127.0.0.1';
		$this->port = getenv('COUCHBASE_PORT') ?: conf('COUCHBASE_PORT') ?: 8092;
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
