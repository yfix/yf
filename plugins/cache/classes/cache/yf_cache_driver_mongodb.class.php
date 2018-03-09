<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_mongodb extends yf_cache_driver {

	const DATA_FIELD = 'd';
	const EXPIRATION_FIELD = 'e';

	/** @var object internal @conf_skip */
	public $_connection = null;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			return call_user_func_array([$this->_connection, $name], $args);
		}
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->_connection = mongodb();
		$this->_connection->connect();
	}

	/**
	*/
	function is_ready() {
		return $this->_connection && $this->_connection->is_ready();
	}

	/**
	*/
	function get($name, $ttl = 0, $params = []) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->get($name, $ttl, $params);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->set($name, $data, $ttl);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->del($name) ?: null;
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->flush() ?: null;
	}

	/**
	*/
	function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->stats() ?: null;
	}
}
