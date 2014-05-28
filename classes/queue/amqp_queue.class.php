<?php
require_once dirname(INCLUDE_PATH).'/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class amqp_queue{


	private $host = 'localhost'; 
	
	private $port = '5672'; 

	private $login = 'guest'; 
	
	private $pswd = 'guest'; 

	private $queue = false;

	function _init(){
		//init namespaces
		///require_once 'queue/vendor_autoload.php';
		
	}

	function conf($host = false, $port = false, $login = false, $pswd = false){
		$this->host = $host ? $host : $this->host;
		$this->port= $port ? $port : $this->port;
		$this->login = $login ? $login : $this->login;
		$this->pswd = $pswd ? $pswd : $this->pswd;
		return $this;
	}

	function send($text = false, $queue = false){
		if(empty($text) || empty($queue)){
			return false;
		}

		$connection = new AMQPConnection($this->host, $this->port, $this->login, $this->pswd);
		$channel = $connection->channel();
		try{
			$channel->queue_declare($queue, false, true, false, false);
		} catch (Exception $e){
			echo $e->getMessage();
			return false;
		}

		$prep_text = new AMQPMessage(trim($text), array('delivery_mode' => 2));
		$channel->basic_publish($prep_text, '', $queue);

		return true;
	}

	function get($queue){
		if(empty($queue)){
			return false;
		}

		$connection = new AMQPConnection($this->host, $this->port, $this->login, $this->pswd);
		$channel = $connection->channel();
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

	function delete($queue = false){
		if(empty($queue)){
			return false;
		}

		$connection = new AMQPConnection($this->host, $this->port, $this->login, $this->pswd);
		$channel = $connection->channel();
		try{
			$channel->queue_delete($queue);
		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}

	//Get all messages WITHOUT removing from queue
	function view_all($queue){
		if(empty($queue)){
			return false;
		}

		$connection = new AMQPConnection($this->host, $this->port, $this->login, $this->pswd);
		$channel = $connection->channel();
		try{
			$channel->queue_declare($queue, false, true, false, false, false);
		} catch (Exception $e){
			echo $e->getMessage();
			return false;
		}
		
		$msg = true;
		$all_msgs = array();
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

