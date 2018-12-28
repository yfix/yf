<?php

require __DIR__ . '/_rabbitmq.php';

$ex = new AMQPExchange($ch);
$ex->setName($pubsub_ex_name);
$ex->setType(AMQP_EX_TYPE_FANOUT);
$ex->declareExchange();
/*
$q = new AMQPQueue($ch);
$q->setName($pubsub_q_name);
$q->setFlags(AMQP_DURABLE);
$q->declareQueue();

$q->bind($ex->getName(), $pubsub_q_name);
*/
while (true) {
    $ex->publish('message ' . ++$i, $pubsub_topic_name);
    usleep(1000);
}
