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
		!$this->_connection && $this->connect();
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			return call_user_func_array([$this->_connection, $name], $args);
		}
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
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
	function connect($params = []) {
		if ($this->_connection) {
			return $this->_connection;
		}
		$this->host   = $this->_get_conf('REDIS_HOST', '127.0.0.1', $params);
		$this->port   = (int)$this->_get_conf('REDIS_PORT', '6379', $params);
		$this->prefix = $this->_get_conf('REDIS_PREFIX', '', $params);
		$this->prefix = $this->prefix ? $this->prefix .':' : '';
		$this->opt_connect_timeout = $this->_get_conf('REDIS_OPT_CONNECT_TIMEOUT', '1', $params);
		$this->opt_connect_delay = $this->_get_conf('REDIS_OPT_CONNECT_DELAY', '100', $params);

		$redis = null;
		if ($this->driver == 'phpredis') {
			$redis = new Redis();
			$redis->connect($this->host, (int)$this->port/*, (float)$this->opt_connect_timeout, null, (int)$this->opt_connect_delay*/);
			$redis->setOption( Redis::OPT_PREFIX, $this->prefix );
		} elseif ($this->driver == 'predis') {
			require_php_lib('predis');
			$redis = new Predis\Client([
				'scheme' => 'tcp',
				'host'   => $this->host,
				'port'   => (int)$this->port,
			]);
		}
		$this->_connection = $redis;
		return $this->_connection;
	}

	/**
	*/
	function conf($opt = []) {
		!$this->_connection && $this->connect();
		foreach ((array)$opt as $k => $v) {
			$this->_connection->setOption($k, $v);
		}
	}

	/**
	*/
	function get($key) {
		!$this->_connection && $this->connect();
		return $this->_connection->get($key);
	}

	/**
	*/
	function set($key, $val) {
		!$this->_connection && $this->connect();
		return $this->_connection->set($key, $val);
	}

	/**
	*/
	function del($key) {
		!$this->_connection && $this->connect();
		return $this->_connection->del($key);
	}

	/**
	*/
	function lpush($key, $val) {
		!$this->_connection && $this->connect();
		return $this->_connection->lpush($key, $val);
	}

	/**
	*/
	function rpop($key) {
		!$this->_connection && $this->connect();
		return $this->_connection->rpop($key);
	}

	/**
	*/
	function lrem($key, $num) {
		!$this->_connection && $this->connect();
		return $this->_connection->lrem($key, $num);
	}

	/**
	*/
	function lrange($key, $from = 0, $to = -1) {
		!$this->_connection && $this->connect();
		return $this->_connection->lrange($key, $from, $to);
	}

	/**
	*/
	function pub($channel, $what) {
		!$this->_connection && $this->connect();
		return $this->_connection->publish($channel, $what);
	}

	/**
	*/
	function sub($channels, $callback) {
		!$this->_connection && $this->connect();
		return $this->_connection->subscribe($channels, $callback);
	}
}
