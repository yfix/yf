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
		!$this->_is_connection && $this->connect();
		$this->_connection_pub->conf($params);
		$this->_connection_sub->conf($params);
		return $this;
	}

	/**
	*/
	function connect($params = []) {
		if (!$this->_is_connection) {
			$override = [
				'REDIS_HOST'	=> $this->_get_conf('REDIS_PUBSUB_HOST'),
				'REDIS_PORT'	=> $this->_get_conf('REDIS_PUBSUB_PORT'),
				'REDIS_PREFIX'	=> $this->_get_conf('REDIS_PUBSUB_PREFIX'),
			];
			$this->_connection_pub = clone redis($params);
			$this->_connection_pub->connect($override);
			$this->_connection_sub = clone redis($params);
			$this->_connection_sub->connect($override);
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
