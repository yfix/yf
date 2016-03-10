<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_couchbase extends yf_cache_driver {

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
		$this->_connection = couchbase()->connect();
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
		if ($ttl > 30 * 24 * 3600) {
			$ttl = time() + $ttl;
		}
		return $this->_connection->set($name, $data, (int) $ttl);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->delete($name);
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->flush();
	}

	/**
	*/
	function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		$stats   = $this->_connection->getStats();
		$servers = $this->_connection->getServers();
		$server  = explode(':', $servers[0]);
		$key	 = $server[0] . ':' . '11210';
		$stats   = $stats[$key];
		return array(
			'hits'		=> $stats['get_hits'],
			'misses'	=> $stats['get_misses'],
			'uptime'	=> $stats['uptime'],
			'mem_usage'	=> $stats['bytes'],
			'mem_avail'	=> $stats['limit_maxbytes'],
		);
	}
}
