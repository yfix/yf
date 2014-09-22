<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_couchbase extends yf_cache_driver {

	/** @var object internal @conf_skip */
	public $_connection = null;
	/** @var boo; internal @conf_skip */
	public $_connected_ok = false;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_connected) && method_exists($this->_connected, $name)) {
			return call_user_func_array(array($this->_connected, $name), $args);
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _init() {
// TODO
	}

	/**
	*/
	function is_ready() {
		return isset($this->_connection) && $this->_connected_ok;
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
