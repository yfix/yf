<?php

require __DIR__ . '/../_rabbitmq.php';

$myConsumer = function ($msg) {
    echo $msg, "\n";
};

$consumer = new Thumper\Consumer($registry->getConnection());
$consumer->setExchangeOptions(['name' => 'logs-exchange', 'type' => 'topic']);
$consumer->setQueueOptions(['name' => $argv[2] . '-queue']);
$consumer->setRoutingKey($argv[1]);
$consumer->setCallback($myConsumer);
$consumer->consume(5);
