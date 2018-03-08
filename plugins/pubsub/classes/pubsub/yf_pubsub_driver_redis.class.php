<?php

load('pubsub_driver', '', 'classes/pubsub/');
class yf_pubsub_driver_redis extends yf_pubsub_driver {

	public $_is_connection  = null;
	public $_connection_pub = null;
	public $_connection_sub = null;

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
	function pub_conf($params = []) {
		!$this->_is_connection && $this->connect();
		$this->_connection_pub->conf($params);
		return $this;
	}

	/**
	*/
	function sub_conf($params = []) {
		!$this->_is_connection && $this->connect();
		$this->_connection_sub->conf($params);
		return $this;
	}

	/**
	*/
	function reconnect_pub() {
		$this->_connection_pub->reconnect();
	}

	/**
	*/
	function reconnect_sub() {
		$this->_connection_sub->reconnect();
	}

	/**
	*/
	function connect( $options = [] ) {
		if (!$this->_is_connection) {
			if( !$options ) {
				$options = [
					'REDIS_HOST'	=> $this->_get_conf('REDIS_PUBSUB_HOST'),
					'REDIS_PORT'	=> $this->_get_conf('REDIS_PUBSUB_PORT'),
					'REDIS_PREFIX'	=> $this->_get_conf('REDIS_PUBSUB_PREFIX'),
				];
			}
			$this->_connection_pub = redis()->factory( $options );
			$options[ 'is_new' ] = true;
			$this->_connection_sub = redis()->factory( $options );
			$this->_is_connection = true;
		}
		if( !$this->_connection_pub->is_connection() ) {
			$this->reconnect_pub();
		}
		if( !$this->_connection_sub->is_connection() ) {
			$this->reconnect_sub();
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
		!$this->_is_connection && $this->connect();
		return $this->_connection_pub->pub($channel, $what);
	}

	/**
	*/
	function sub($channels, $callback) {
		!$this->_is_connection && $this->connect();
		return $this->_connection_sub->sub($channels, $callback);
	}

}
