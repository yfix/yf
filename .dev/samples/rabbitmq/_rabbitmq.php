<?php

$conf = require __DIR__ . '/_conf.php';

// DOCS https://github.com/pdezwart/php-amqp/tree/master/tests
$cnn = new AMQPConnection([
    'host' => $conf['host'] ?: 'localhost',
    'vhost' => $conf['vhost'] ?: '/',
    'port' => $conf['port'] ?: 5672,
    'login' => $conf['login'] ?: 'user',
    'password' => $conf['password'] ?: 'password',
]);
$cnn->connect();

$ch = new AMQPChannel($cnn);

$pubsub_ex_name = 'test-pubsub-exchange';
$pubsub_q_name = 'test-pubsub-queue';
$pubsub_topic_name = 'test-pubsub-topic';

$queues_ex_name = 'test-queue-exchange';
$queues_q_name = 'test-queue-queue';
$queues_topic_name = 'test-queue-topic';

$rpc_ex_name = 'test-rpc-exchange';
$rpc_q_name = 'test-rpc-queue';
$rpc_topic_name = 'test-rpc-topic';
