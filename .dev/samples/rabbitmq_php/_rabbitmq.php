<?php

require_once dirname(dirname(dirname(__DIR__))).'/share/services/amqplib.php';

$conf = require __DIR__.'/_conf.php';

# DOCS https://www.rabbitmq.com/tutorials/tutorial-three-php.html
$connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
	$conf['host'] ?: 'localhost',
	$conf['port'] ?: 5672,
	$conf['login'] ?: 'user',
	$conf['password'] ?: 'password'
);
$channel = $connection->channel();
