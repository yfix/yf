<?php

$conf = require __DIR__.'/_conf.php';

$cnn = new AMQPConnection([
	'host' => $conf['host'] ?: 'localhost',
	'vhost' => $conf['vhost'] ?: '/',
	'port' => $conf['port'] ?: 5763,
	'login' => $conf['login'] ?: 'user',
	'password' => $conf['password'] ?: 'password'
]);
$cnn->connect();

$ch = new AMQPChannel($cnn);

$ex = new AMQPExchange($ch);
$ex->setName('test-exchange');
$ex->setType(AMQP_EX_TYPE_FANOUT);
$ex->declareExchange();

$routing_key = 'test-queue';

$queue = new AMQPQueue($ch);
$queue->setName($routing_key);
$queue->setFlags(AMQP_DURABLE);
$queue->declareQueue();
