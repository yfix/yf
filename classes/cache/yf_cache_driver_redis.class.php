<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_redis extends yf_cache_driver {

	/** @var object internal @conf_skip */
	public $_connection = null;
	/** @var int */
	public $DEFAULT_TTL = 3600;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		if (!$this->is_ready()) {
			return null;
		}
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			return call_user_func_array([$this->_connection, $name], $args);
		}
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _get_conf($name, $default = null, array $params = []) {
		if (isset($params[$name]) && $val = $params[$name]) {
			return $val;
		}
		if ($val = getenv($name)) {
			return $val;
		}
		if ($val = conf($name)) {
			return $val;
		}
		if (defined($name) && ($val = constant($name)) != $name) {
			return $val;
		}
		return $default;
	}

	/**
	*/
	function _init() {
		$override = [
			'REDIS_HOST'	=> $this->_get_conf('REDIS_CACHE_HOST'),
			'REDIS_PORT'	=> $this->_get_conf('REDIS_CACHE_PORT'),
			'REDIS_PREFIX'	=> $this->_get_conf('REDIS_CACHE_PREFIX'),
		];
		$this->_connection = redis();
		$this->_connection->connect($override);
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
		$res = $this->_connection->get($name);
#		return $res === false || $res === null ? null : $res;
		return $res ? json_decode($res, true) : null;
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		$data = json_encode($data);
		return $this->_connection->setex($name, $ttl ?: $this->DEFAULT_TTL, $data) ?: null;
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->del($name) > 0 ? true : null;
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->flushDB() ?: null;
	}

	/**
	*/
	function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		$info = $this->_connection->info();
		return [
			'hits'		=> false,
			'misses'	=> false,
			'uptime'	=> $info['uptime_in_seconds'],
			'mem_usage'	=> $info['used_memory'],
			'mem_avail'	=> false,
		];
	}
}
