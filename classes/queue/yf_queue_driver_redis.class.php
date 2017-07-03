<?php

load('queue_driver', 'framework', 'classes/queue/');
class yf_queue_driver_redis extends yf_queue_driver {

	private $_connection = null;

	/**
	*/
	function _init() {
		$this->connect();
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
	function conf($params = []) {
		!$this->_connection && $this->connect();
		$this->_connection->conf($params);
		return $this;
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
				$this->_connection = clone redis($params);
			} else {
				$this->_connection = redis($params);
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
		return $this->_connection->lpush($queue, $what);
	}

	/**
	*/
	function get($queue) {
		return $this->_connection->rpop($queue);
	}

	/**
	*/
	function del($queue) {
		return $this->_connection->lrem($queue, 1);
	}

	/**
	*/
	function all($queue) {
		return $this->_connection->lrange($queue, 0, -1);
	}
}
