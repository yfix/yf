<?php

/**
* Redis API wrapper
*/
class yf_wrapper_redis {

	public $driver = 'phpredis'; // predis|phpredis
	public $host   = '127.0.0.1';
	public $port   = 6379;
	public $prefix = '';
	static $_connection = null;

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

	function __clone() {
		$this->_connection = null;
	}

	/**
	*/
	function is_ready() {
		!$this->_connection && $this->connect();
		return (bool)$this->_connection;
	}

	/**
	*/
	function _get_conf($name, $default) {
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
	function connect($params = []) {
		if ($this->_connection) {
			return $this->_connection;
		}
		$this->host   = $this->_get_conf('REDIS_HOST', '127.0.0.1');
		$this->port   = (int)$this->_get_conf('REDIS_PORT', '6379');
		$this->prefix = $this->_get_conf('REDIS_PREFIX', '');
		$this->prefix = $this->prefix ? $this->prefix .':' : '';

		$redis = null;
		if ($this->driver == 'predis') {
			require_php_lib('predis');
			$redis = new Predis\Client([
				'scheme' => 'tcp',
				'host'   => $this->host,
				'port'   => (int)$this->port,
			]);
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
	function conf($opt = []) {
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
