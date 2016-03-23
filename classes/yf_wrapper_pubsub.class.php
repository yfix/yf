<?php

/**
* Core PUB/SUB events wrapper
*/
class yf_wrapper_pubsub {

	public $driver = 'redis';
	public $_connection = null;

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
			$this->_connection = _class('pubsub_driver_'.$this->driver, 'classes/pubsub/');
			$this->_connection->connect();
		}
		return $this->_connection;
	}

	/**
	* Check if system is ready
	*/
	function is_ready() {
		!$this->_connection && $this->connect();
		return (bool)$this->_connection;
	}

	/**
	* Publish new event
	*/
	function pub($channel, $what) {
		!$this->_connection && $this->connect();
		return $this->_connection->pub($channel, $what);
	}

	/**
	* Subscribe for one or more events
	*/
	function sub($channels, $callback) {
		!$this->_connection && $this->connect();
		return $this->_connection->sub($channels, $callback);
	}

	/**
	* Configure driver
	*/
	function conf($params = array()) {
		!$this->_connection && $this->connect();
		return $this->_connection->conf($params);
	}
}
