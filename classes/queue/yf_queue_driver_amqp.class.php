<?php

load('queue_driver', 'framework', 'classes/queue/');
class yf_queue_driver_amqp extends yf_queue_driver {

	private $host	= 'localhost'; 
	private $port	= '5672'; 
	private $login	= 'guest'; 
	private $pswd	= 'guest'; 
	private $queue	= false;
	private $_connection = null;

	/**
	*/
	function _init() {
		$this->connect();
	}

	/**
	*/
	function conf($params = []) {
		if (is_array($params) && !empty($params)) {
			isset($params['host'])	&& $this->host	= $params['host'];
			isset($params['port'])	&& $this->port	= $params['port'];
			isset($params['login'])	&& $this->login	= $params['login'];
			isset($params['pswd'])	&& $this->pswd	= $params['pswd'];
		}
		return $this;
	}

	/**
	*/
	function connect($params = []) {
		if (!$this->_connection) {
			if ($params) {
				$this->conf($params);
			}
			require_php_lib('php_amqplib');
			$this->_connection = new \PhpAmqpLib\Connection\AMQPConnection($this->host, $this->port, $this->login, $this->pswd);
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
		if (empty($queue) || empty($what)) {
			return false;
		}
		!$this->_connection && $this->connect();

		$channel = $this->_connection->channel();
		try{
			$channel->queue_declare($queue, false, true, false, false);
		} catch (Exception $e){
			echo $e->getMessage();
			return false;
		}
		$prepared = new \PhpAmqpLib\Message\AMQPMessage(trim($what), ['delivery_mode' => 2]);
		$channel->basic_publish($prepared, '', $queue);

		return true;
	}

	/**
	*/
	function get($queue) {
		if (empty($queue)) {
			return false;
		}
		!$this->_connection && $this->connect();

		$channel = $this->_connection->channel();
		try{
			$channel->queue_declare($queue, false, true, false, false);
		} catch (Exception $e){
			echo $e->getMessage();
			return false;
		}
		$msg = $channel->basic_get($queue);
		$channel->basic_ack($msg->delivery_info['delivery_tag']);
		$msg = !empty($msg->body) ? trim($msg->body) : false;
		
		return $msg;
	}

	/**
	*/
	function del($queue) {
		if (empty($queue)) {
			return false;
		}
		!$this->_connection && $this->connect();

		$channel = $this->_connection->channel();
		try{
			$channel->queue_delete($queue);
		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}

	/**
	* Get all messages WITHOUT removing from queue
	*/
	function all($queue) {
		if (empty($queue)) {
			return false;
		}
		!$this->_connection && $this->connect();

		$channel = $this->_connection->channel();
		try{
			$channel->queue_declare($queue, false, true, false, false, false);
		} catch (Exception $e){
			echo $e->getMessage();
			return false;
		}
		$msg = true;
		$all_msgs = [];
		while($msg){
			$msg = $channel->basic_get($queue);
			$channel->basic_cancel($msg->delivery_info['delivery_tag']);
			$msg = !empty($msg->body) ? $msg->body : false;
			if($msg){
				$all_msgs[] = trim($msg);
			}
		}
		return $all_msgs;
	}
}
