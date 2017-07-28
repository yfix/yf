<?php

/**
* Rabbitmq API wrapper
*
* EXAMPLES: https://github.com/pdezwart/php-amqp/tree/master/tests
* EXAMPLES: https://otokarev.com/2014/11/25/rabbitmq-primer-realizatsii-rpc-na-php/
*/
class yf_wrapper_rabbitmq {

	private $host	= 'localhost';
	private $port	= 5672;
	private $login	= 'user';
	private $password	= 'password';
	private $vhost	= '/';
	private $queue	= false;
	private $_connection = null;
	private $_channel = null;
	private $_exchange = null;
	public $driver = 'pecl'; // pecl | amqplib
	public $routing_key = 'mykey';

	/**
	*/
	function __clone() {
		$this->_connection = null;
	}

	/**
	*/
	function conf($params = []) {
		if ($this->driver == 'pecl' && !extension_loaded('amqp')) {
			$this->driver = null;
#			$this->driver = 'amqplib';
		}
		if (is_array($params) && !empty($params)) {
			isset($params['host'])	&& $this->host	= $params['host'];
			isset($params['port'])	&& $this->port	= $params['port'];
			isset($params['login'])	&& $this->login	= $params['login'];
			isset($params['password'])	&& $this->password	= $params['password'];
			isset($params['vhost'])	&& $this->vhost	= $params['vhost'];
			isset($params['routing_key'])	&& $this->routing_key	= $params['routing_key'];
		}
		return $this;
	}

	/**
	*/
	function connect($params = []) {
		if ($this->_connection) {
			return $this->_connection;
		}
		if ($params) {
			$this->conf($params);
		}
		if ($this->driver == 'pecl') {
			$this->_connection = new AMQPConnection([
				'host'	=> $this->host,
				'vhost'	=> $this->vhost,
				'port'	=> $this->port,
				'login'	=> $this->login,
				'password' => $this->password,
			]);
			$this->_connection->connect();
		} elseif ($this->driver == 'amqplib') {
#			require_php_lib('amqplib');
#			$this->_connection = new \PhpAmqpLib\Connection\AMQPConnection($this->host, $this->port, $this->login, $this->password);
		}
		return $this->_connection;
	}

	/**
	*/
	function init_channel($params = []) {
		if (!$this->_channel) {
			$cnn = $this->connect($params);
			$this->_channel = new AMQPChannel($cnn);
		}
		return $this->_channel;
	}

	/**
	*/
	function init_exchange($name, $params = []) {
		if (!$name) {
			return null;
		}
		if (!isset($this->_exchange[$name])) {
			$ch = $this->init_channel($params);
			$ex = new AMQPExchange($ch);
			$ex->setName($name);
			$ex->setType($params['exchange_type'] ?: AMQP_EX_TYPE_FANOUT);
			$ex->declareExchange();
			$this->_exchange[$name] = $ex;
		}
		return $this->_exchange[$name];
	}

	/**
	*/
	function init_queue($name, $params = []) {
		if (!$name) {
			return null;
		}
		if (!isset($this->_queue[$name])) {
			$ch = $this->init_channel($params);
			$q = new AMQPQueue($ch);
			$q->setName($name);
			$q->setFlags($params['queue_flags'] ?: AMQP_DURABLE);
			$q->declareQueue();
			$this->_queue[$name] = $q;
		}
		return $this->_queue[$name];
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
		if (!$queue || !strlen($what)) {
			return false;
		}
		!$this->_connection && $this->connect();

		if ($this->driver == 'pecl') {
			$ex = $this->_init_exchange($queue);
			$ex->publish($what, $this->routing_key);
		} elseif ($this->driver == 'amqplib') {
# TODO
		}
		return true;
	}

	/**
	*/
	function get($queue) {
		if (!$queue) {
			return false;
		}
		!$this->_connection && $this->connect();

		if ($this->driver == 'pecl') {
			$q = $this->_init_queue($queue);
			$msg = $q->get(AMQP_AUTOACK);
		} elseif ($this->driver == 'amqplib') {
# TODO
		}
		return $msg;
	}

	/**
	* Publish new event
	*/
	function pub($channel, $what) {
		if (!$channel || !strlen($what)) {
			return false;
		}
		!$this->_connection && $this->connect();

		if ($this->driver == 'pecl') {
			$ex = $this->_init_exchange($channel);
			$ex->publish($what, $this->routing_key);
		} elseif ($this->driver == 'amqplib') {
# TODO
		}
	}

	/**
	* Subscribe for one or more events
	*/
	function sub($channel, $callback) {
		if (!$channel || !is_callable($callback)) {
			return false;
		}
		!$this->_connection && $this->connect();

		if ($this->driver == 'pecl') {
			$q = $this->_init_queue($channel);
			return $q->consume($callback, AMQP_AUTOACK);
		} elseif ($this->driver == 'amqplib') {
# TODO
		}
	}
}
