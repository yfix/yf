<?php

load('queue_driver', 'framework', 'classes/queue/');
class yf_queue_redis extends yf_queue_driver {

	private $_connection = null;

	/**
	*/
	function _init() {
		$this->connect();
	}

	/**
	*/
	function conf($params = array()) {
		!$this->_connection && $this->connect();
		$this->_connection->conf($params);
		return $this;
	}

	/**
	*/
	function connect($params = array()) {
		if (!$this->_connection) {
			$this->_connection = redis($params);
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
