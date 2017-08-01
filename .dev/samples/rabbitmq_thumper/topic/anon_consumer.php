<?php

require __DIR__ . '/../_rabbitmq.php';

$myConsumer = function ($msg) {
    echo $msg, "\n";
};

$consumer = new Thumper\AnonConsumer($registry->getConnection());
$consumer->setExchangeOptions(['name' => 'logs-exchange', 'type' => 'topic']);
$consumer->setRoutingKey($argv[1]);
$consumer->setCallback($myConsumer);
$consumer->consume(5);
