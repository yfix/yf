<?php

load('pubsub_driver', 'framework', 'classes/pubsub/');
class yf_pubsub_driver_redis extends yf_pubsub_driver {

	private $_is_connection  = null;
	private $_connection_pub = null;
	private $_connection_sub = null;

	/**
	*/
	function _init() {
		$this->connect();
	}

	/**
	*/
	function conf($params = array()) {
		!$this->_is_connection && $this->connect();
		$this->_connection_pub->conf($params);
		$this->_connection_sub->conf($params);
		return $this;
	}

	/**
	*/
	function connect($params = array()) {
		if (!$this->_is_connection) {
			$this->_connection_pub = clone redis($params);
			$this->_connection_sub = clone redis($params);
			$this->_connection_pub->connect();
			$this->_connection_sub->connect();
		}
		return $this->_is_connection;
	}

	/**
	*/
	function is_ready() {
		!$this->_is_connection && $this->connect();
		return (bool)$this->_connection;
	}

	/**
	*/
	function pub($channel, $what) {
		return $this->_connection_pub->pub($channel, $what);
	}

	/**
	*/
	function sub($channels, $callback) {
		return $this->_connection_sub->sub($channels, $callback);
	}
}
