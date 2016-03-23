<?php

/**
* Core jobs wrapper
*/
class yf_wrapper_job {

	public $driver = 'redis';
	public $_connection = null;

	public $statuses = [
		'waiting',  // Job is still queued
		'running',  // Job is currently running
		'failed',   // Job has failed
		'complete', // Job is complete
	];

// TODO

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

	/**
	* Do connect to the low level driver
	*/
	function connect() {
		if (!$this->_connection) {
			$this->_connection = queue()->connect();
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
	* Get job status
	*/
	function status($queue = false, $job) {
		!$this->_connection && $this->connect();
		return $this->_connection->del($queue);
	}

	/**
	* Configure driver
	*/
	function conf($params = array()) {
		!$this->_connection && $this->connect();
		return $this->_connection->conf($params);
	}
}
