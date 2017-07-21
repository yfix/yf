<?php

require __DIR__.'/_rabbitmq.php';

$queue->bind($ex->getName(), $routing_key);
while(true) {
	$ex->publish('message '.++$i, $routing_key);
	usleep(10000);
}
