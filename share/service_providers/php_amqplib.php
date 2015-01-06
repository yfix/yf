#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/php-amqplib.git' => 'php_amqplib/');
$autoload_config = array('php_amqplib/PhpAmqpLib/' => 'PhpAmqpLib');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$host = 'localhost'; 
	$port = '5672'; 
	$login = 'guest'; 
	$pswd = 'guest'; 
	$connection = new PhpAmqpLib\Connection\AMQPConnection($host, $port, $login, $pswd);
	var_dump($connection);
}