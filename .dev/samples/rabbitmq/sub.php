<?php

require __DIR__ . '/_rabbitmq.php';

$q = new AMQPQueue($ch);
$q->setName($pubsub_q_name);
$q->setFlags(AMQP_DURABLE | AMQP_IFUNUSED);
$q->declareQueue();

$callback = function (AMQPEnvelope $msg, AMQPQueue $q) {
    echo ' [x] Received: ', $msg->getBody(), PHP_EOL;
    //	$q->ack($msg->getDeliveryTag());
};
$q->consume($callback, AMQP_AUTOACK);
//$q->consume($callback/*, AMQP_AUTOACK*/);
