<?php

require __DIR__ . '/../_rabbitmq.php';

$myConsumer = function ($msg) {
    echo $msg, "\n";
};

$consumer = new Thumper\Consumer($registry->getConnection());
$consumer->setExchangeOptions(['name' => 'hello-exchange', 'type' => 'direct']);
$consumer->setQueueOptions(['name' => 'hello-queue']);
$consumer->setCallback($myConsumer); //myConsumer could be any valid PHP callback
$consumer->consume(5); //5 is the number of messages to consume
