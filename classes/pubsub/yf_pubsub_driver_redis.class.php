<?php

load('pubsub_driver', 'framework', 'classes/pubsub/');
class yf_pubsub_driver_redis extends yf_pubsub_driver {

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
			$this->_connection = clone redis($params);
			$this->_connection->connect();
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
	function pub($channel, $what) {
		return $this->_connection->pub($channel, $what);
	}

	/**
	*/
	function sub($channels, $callback) {
		return $this->_connection->sub($channels, $callback);
	}
}
