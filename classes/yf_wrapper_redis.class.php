<?php

/**
* Redis API wrapper
*/
class yf_wrapper_redis {

	public $driver = 'phpredis'; // predis|phpredis
	public $host   = '127.0.0.1';
	public $port   = 6379;
	public $prefix = '';
	private $_connection = null;

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

	function __clone() {
		$this->_connection = null;
	}

	/**
	*/
	function _init() {
		$this->host   = getenv('REDIS_HOST')   ?: conf('REDIS_HOST')   ?: defined('REDIS_HOST')   ? REDIS_HOST : '127.0.0.1';
		$this->port   = getenv('REDIS_PORT')   ?: conf('REDIS_PORT')   ?: defined('REDIS_PORT')   ? REDIS_PORT : 6379;
		$this->prefix = getenv('REDIS_PREFIX') ?: conf('REDIS_PREFIX') ?: defined('REDIS_PREFIX') ? REDIS_PREFIX : '';
		$this->port   = intval($this->port);
		$this->prefix = $this->prefix ? $this->prefix .':' : '';
	}

	/**
	*/
	function is_ready() {
		!$this->_connection && $this->connect();
		return (bool)$this->_connection;
	}

	/**
	*/
	function connect($params = array()) {
		if ($this->_connection) {
			return $this->_connection;
		}
		$redis = null;
		if ($this->driver == 'predis') {
			require_php_lib('predis');
			$redis = new Predis\Client(array(
				'scheme' => 'tcp',
				'host'   => $this->host,
				'port'   => (int)$this->port,
			));
		} elseif ($this->driver == 'phpredis') {
			$redis = new Redis();
			$redis->connect($this->host, (int)$this->port);
			$redis->setOption( Redis::OPT_PREFIX, $this->prefix );
		}
		$this->_connection = $redis;
		return $this->_connection;
	}

	/**
	*/
	function conf($opt = array()) {
		foreach ((array)$opt as $k => $v) {
			$this->_connection->setOption($k, $v);
		}
	}

	/**
	*/
	function get($key) {
		return $this->_connection->get($key);
	}

	/**
	*/
	function set($key, $val) {
		return $this->_connection->set($key, $val);
	}

	/**
	*/
	function del($key) {
		return $this->_connection->del($key);
	}

	/**
	*/
	function lpush($key, $val) {
		return $this->_connection->lpush($key, $val);
	}

	/**
	*/
	function rpop($key) {
		return $this->_connection->rpop($key);
	}

	/**
	*/
	function lrem($key, $num) {
		return $this->_connection->lrem($key, $num);
	}

	/**
	*/
	function lrange($key, $from = 0, $to = -1) {
		return $this->_connection->lrange($key, $from, $to);
	}

	/**
	*/
	function pub($channel, $what) {
		return $this->_connection->publish($channel, $what);
	}

	/**
	*/
	function sub($channels, $callback) {
		return $this->_connection->subscribe($channels, $callback);
	}
}
