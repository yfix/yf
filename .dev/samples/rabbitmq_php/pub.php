<?php

require __DIR__ . '/_rabbitmq.php';

$channel->exchange_declare('logs', 'fanout', false, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'info: Hello World ' . microtime(true) . '!';
}
$msg = new \PhpAmqpLib\Message\AMQPMessage($data);

$channel->basic_publish($msg, 'logs');

echo ' [x] Sent ', $data, PHP_EOL;

$channel->close();
$connection->close();
