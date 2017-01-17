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
	public $_log = [];
	public $LOG_LIMIT = 1000;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		!$this->_connection && $this->connect();
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			$result = call_user_func_array([$this->_connection, $name], $args);
		} else {
			$result = main()->extend_call($this, $name, $args);
		}
		if (DEBUG_MODE) {
			$this->_query_log($name, $args, $result, microtime(true) - $time_start);
		}
		return $result;
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

	/**
	*/
	function _query_log($func, $args = [], $result = null, $exec_time = 0.0) {
		// Save memory on high number of query log entries
		if ($this->LOG_LIMIT && count($this->_log) >= $this->LOG_LIMIT) {
			return false;
		}
		$this->_log[] = [
			'func'		=> $func,
			'args'		=> $args,
			'result'	=> $result,
			'exec_time'	=> round($exec_time, 5),
			'trace'		=> $this->_trace_string(2),
		];
		return count($this->_log) - 1;
	}

	/**
	* Print nice
	*/
	function _trace_string($from = 1, $to = 1) {
		$e = new Exception();
		return implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), $from, -$to));
	}
}
