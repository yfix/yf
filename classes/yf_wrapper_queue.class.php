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
		// Support for driver-specific methods
		if (is_object($this->_connection) && method_exists($this->_connection, $name)) {
			return call_user_func_array([$this->_connection, $name], $args);
		}
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Do connect to the low level driver
	*/
	function connect() {
		if (!$this->_connection) {
			$this->_connection = _class('queue_driver_'.$this->driver, 'classes/queue/');
			$this->_connection->connect();
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
	function add($queue, $what) {
		!$this->_connection && $this->connect();
		return $this->_connection->add($queue, $what);
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
	function conf($params = []) {
		!$this->_connection && $this->connect();
		return $this->_connection->conf($params);
	}

	/**
	* Listen to queue
	*/
	function listen($qname, $callback, $params = []) {
		if (!$this->is_ready()) {
			return false;
		}
		while (true) {
			$data = $this->get($qname);
			if ($data) {
				$callback($data, $params);
			}
			usleep($params['usleep'] ?: 100000);
		}
	}
}
