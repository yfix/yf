<?php

require __DIR__ . '/_rabbitmq.php';

$q = new AMQPQueue($ch);
$q->setName($queues_q_name);
$q->setFlags(AMQP_DURABLE);
$q->declareQueue();

$callback = function (AMQPEnvelope $msg, AMQPQueue $q) {
    echo ' [x] Received: ', $msg->getBody(), PHP_EOL;
};
$q->consume($callback, AMQP_AUTOACK);
