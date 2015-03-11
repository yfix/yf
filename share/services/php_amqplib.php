#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/php-amqplib.git' => 'php_amqplib/'),
	'autoload_config' => array('php_amqplib/PhpAmqpLib/' => 'PhpAmqpLib'),
	'example' => function() {
		$host = 'localhost'; 
		$port = '5672'; 
		$login = 'guest'; 
		$pswd = 'guest'; 
		$connection = new PhpAmqpLib\Connection\AMQPConnection($host, $port, $login, $pswd);
		var_dump($connection);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
