<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_redis extends yf_cache_driver {

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
// TODO
		return false;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->redis->get($name);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		if ($ttl > 0) {
			return $this->redis->setex($name, $ttl, $data);
		}
		return $this->redis->set($name, $data);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->redis->delete($name) > 0;
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->redis->flushDB();
	}

	/**
	*/
	protected function stats() {
		if (!$this->is_ready()) {
			return null;
		}
		$info = $this->redis->info();
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
	 * igbinary support, that is used. Otherwise the default PHP serializer is
	 * used.
	 *
	 * @return integer One of the Redis::SERIALIZER_* constants
	 */
	protected function _get_serializer() {
		return defined('Redis::SERIALIZER_IGBINARY') ? Redis::SERIALIZER_IGBINARY : Redis::SERIALIZER_PHP;
	}
}
