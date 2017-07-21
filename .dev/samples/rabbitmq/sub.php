<?php

require __DIR__.'/_rabbitmq.php';

$callback_func = function(AMQPEnvelope $message, AMQPQueue $q) use (&$max_jobs) {
	echo ' [x] Received: ', $message->getBody(), PHP_EOL;
	sleep(sleep(substr_count($message->getBody(), '.')));
	echo ' [X] Done', PHP_EOL;
	$q->ack($message->getDeliveryTag());
};
$queue->consume($callback_func);
