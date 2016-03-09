<?php

/**
* Core queue wrapper
*/
class yf_wrapper_queue {

	public $driver = 'redis';
	public $_connection = null;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->connect();
	}

	/**
	* Do connect to the low level driver
	*/
	function connect() {
		if (!$this->_connection) {
			$this->_connection = _class('queue_driver_'.$this->driver, 'classes/queue/')->connect();
		}
		return $this->_connection;
	}

	/**
	* Check if queue system is ready for processing
	*/
	function is_ready() {
		!$this->_connection && $this->connect();
		return (bool)$this->_connection;
	}

	/**
	* Add new item into named queue
	*/
	function add($text = false, $queue = false) {
		!$this->_connection && $this->connect();
		return $this->_connection->add($text, $queue);
	}

	/**
	* Get one item from named queue (dequeue)
	*/
	function get($queue) {
		!$this->_connection && $this->connect();
		return $this->_connection->get($queue);
	}

	/**
	* Delete one item from the queue
	*/
	function del($queue = false) {
		!$this->_connection && $this->connect();
		return $this->_connection->del($queue);
	}

	/**
	* Return all data from the queue, but not ewmo (for debug purposes)
	*/
	function all($queue) {
		!$this->_connection && $this->connect();
		return $this->_connection->all($queue);
	}

	/**
	* Configure driver
	*/
	function conf($params = array()) {
		!$this->_connection && $this->connect();
		return $this->_connection->conf($params);
	}
}
