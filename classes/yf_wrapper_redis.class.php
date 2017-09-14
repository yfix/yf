<?php

/**
* Redis API wrapper
*/
class yf_wrapper_redis {

	public $name   = 'REDIS'; // instance name
	public $driver = 'phpredis'; // predis|phpredis
	public $host   = '127.0.0.1';
	public $port   = 6379;
	public $prefix = '';
	public $timeout        = 0;
	public $retry_interval = 100;
	public $read_timeout   = 0;
	public $_is_conf       = false;

	public $call_try   = 3;
	public $call_delay = 1000000; // msec

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
		! $this->is_connection() && $this->reconnect();
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			$result = $this->call_try( $name, $args );
		} else {
			$result = main()->extend_call($this, $name, $args);
		}
		if (DEBUG_MODE) {
			$this->_query_log($name, $args, $result, microtime(true) - $time_start);
		}
		return $result;
	}

	function call_try( $name, $args ) {
		$call_try = $this->call_try;
		while( $call_try > 0 ) {
			try {
				$result = call_user_func_array([$this->_connection, $name], $args);
				break;
			} catch( Exception $e ) {
				$result = null;
				--$call_try;
				usleep( $this->call_delay );
				$this->reconnect();
			}
		}
		return $result;
	}

	/**
	*/
	function __clone() {
		$this->disconnect();
		$this->_connection = null;
		$this->_is_conf    = null;
	}

	/**
	*/
	function is_connection() {
		$result = $this->_connection;
		if( $this->driver == 'phpredis' ) {
			$result = $result && $this->_connection->isConnected();
		}
		return( $result );
	}

	/**
	*/
	function is_ready() {
		! $this->is_connection() && $this->reconnect();
		return (bool)$this->_connection;
	}

	/**
	*/
	function _get_conf($name, $default = null, array $params = []) {
		if (isset($this->name) && $name) {
			$name = implode('_', [$this->name, $name]);
		}
		if (isset($params[$name])) {
			return $params[$name];
		}
		$from_env = getenv($name);
		if ($from_env !== false) {
			return $from_env;
		}
		global $CONF;
		if (isset($CONF[$name])) {
			$from_conf = $CONF[$name];
			return $from_conf;
		}
		if (defined($name) && ($val = constant($name)) != $name) {
			return $val;
		}
		return $default;
	}

	/**
	*/
	function reconnect() {
		$this->disconnect();
		$this->connect();
	}

	function disconnect() {
		if( ! $this->is_connection() ) { return( null ); }
		if( $this->driver == 'phpredis' ) {
			$this->_connection->close();
		}
		return( true );
	}

	/**
	*/
	function connect($params = []) {
		if ($this->_connection) {
			return $this->_connection;
		}
		if( !$this->_is_conf ) {
			$this->_is_conf = true;
			$this->host   = $this->_get_conf('HOST', '127.0.0.1', $params);
			$this->port   = (int)$this->_get_conf('PORT', '6379', $params);
			$this->prefix = $this->_get_conf('PREFIX', '', $params);
			$this->prefix = $this->prefix ? $this->prefix .':' : '';
			$this->timeout         = $this->_get_conf( 'TIMEOUT',           0, $params ); // float, sec
			$this->retry_interval  = $this->_get_conf( 'RETRY_INTERVAL',  100, $params ); // int,   msec
			$this->read_timeout    = $this->_get_conf( 'READ_TIMEOUT',     -1, $params ); // float, sec, for subscribe
		}

		$redis = null;
		if ($this->driver == 'phpredis') {
			$redis = new Redis();
			// connect:
			//   host             : string
			//   port             : int,
			//   timeout          : float, value in seconds (optional, default: 0 - unlimited)
			//   reserved         : NULL
			//   retry_interval   : int, value in milliseconds (optional)
			// ? read_timeout     : float, value in seconds (optional, default: 0 - unlimited)
			$redis->connect( $this->host, (int)$this->port,
				(float)$this->timeout,
				null,
				(int)$this->retry_interval
			);
			$redis->setOption( Redis::OPT_PREFIX,       $this->prefix       );
			$redis->setOption( Redis::OPT_READ_TIMEOUT, $this->read_timeout ); // float, sec
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
		! $this->is_connection() && $this->reconnect();
		foreach ((array)$opt as $k => $v) {
			$this->_connection->setOption($k, $v);
		}
	}

	/**
	*/
	function pub($channel, $what) {
		! $this->is_connection() && $this->reconnect();
		$result = $this->call_try( 'publish', [ $channel, $what ] );
		return( $result );
	}

	/**
	*/
	function sub($channels, $callback) {
		! $this->is_connection() && $this->reconnect();
		$result = $this->call_try( 'subscribe', [ $channels, $callback ] );
		return( $result );
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
