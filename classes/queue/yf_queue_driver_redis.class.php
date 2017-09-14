<?php

load('queue_driver', 'framework', 'classes/queue/');
class yf_queue_driver_redis extends yf_queue_driver {

	public $_connection = null;

	/**
	*/
	function _get_conf($name, $default = null, array $params = []) {
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
	function conf($params = []) {
		!$this->_connection && $this->connect();
		$this->_connection->conf($params);
		return $this;
	}

	/**
	*/
	function reconnect() {
		$this->_connection->reconnect();
	}

	/**
	*/
	function connect($params = []) {
		if (!$this->_connection) {
			$override = [
				'REDIS_HOST'	=> $this->_get_conf('REDIS_QUEUE_HOST'),
				'REDIS_PORT'	=> $this->_get_conf('REDIS_QUEUE_PORT'),
				'REDIS_PREFIX'	=> $this->_get_conf('REDIS_QUEUE_PREFIX'),
			];
			$is_override = false;
			foreach ((array)$override as $k => $v) {
				if ($v) {
					$is_override = true;
					break;
				}
			}
			if ($is_override) {
				$this->_connection = clone redis();
			} else {
				$this->_connection = redis();
			}
			$this->_connection->connect($override);
		}
		return $this->_connection;
	}

	/**
	*/
	function is_ready() {
		!$this->_connection && $this->connect();
		return (bool)$this->_connection;
	}

	/**
	*/
	function add($queue, $what) {
		!$this->_connection && $this->connect();
		return $this->_connection->lpush($queue, $what);
	}

	/**
	*/
	function get($queue) {
		!$this->_connection && $this->connect();
		return $this->_connection->rpop($queue);
	}

	/**
	*/
	function del($queue) {
		!$this->_connection && $this->connect();
		return $this->_connection->lrem($queue, 1);
	}

	/**
	*/
	function all($queue) {
		!$this->_connection && $this->connect();
		return $this->_connection->lrange($queue, 0, -1);
	}
}
