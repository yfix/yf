<?php

require __DIR__ . '/_rabbitmq.php';

$channel->exchange_declare('logs', 'fanout', false, false, false);

list($queue_name) = $channel->queue_declare('', false, false, true, false);

$channel->queue_bind($queue_name, 'logs');

echo ' [*] Waiting for logs. To exit press CTRL+C', PHP_EOL;

$callback = function ($msg) {
    echo ' [x] ', $msg->body, PHP_EOL;
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while (count((array) $channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
