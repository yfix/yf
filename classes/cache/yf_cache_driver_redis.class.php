<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_redis extends yf_cache_driver {

	/** @var object internal @conf_skip */
	public $_connection = null;

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
		$this->_connection = redis()->connect();
	}

	/**
	*/
	function is_ready() {
		return $this->_connection && $this->_connection->is_ready();
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->get($name);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		if ($ttl > 0) {
			return $this->_connection->setex($name, $ttl, $data);
		}
		return $this->_connection->set($name, $data);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->delete($name) > 0;
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->flushDB();
	}

	/**
	*/
	function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		$info = $this->_connection->info();
		return array(
			'hits'		=> false,
			'misses'	=> false,
			'uptime'	=> $info['uptime_in_seconds'],
			'mem_usage'	=> $info['used_memory'],
			'mem_avail'	=> false,
		);
	}

	/**
	* Returns the serializer constant to use. If Redis is compiled with
	* igbinary support, that is used. Otherwise the default PHP serializer is used.
	*
	* @return integer One of the Redis::SERIALIZER_* constants
	*/
	function _get_serializer() {
		return defined('Redis::SERIALIZER_IGBINARY') ? Redis::SERIALIZER_IGBINARY : Redis::SERIALIZER_PHP;
	}
}
