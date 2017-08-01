<?php

require __DIR__ . '/../_rabbitmq.php';

$producer = new Thumper\Producer($registry->getConnection());
$producer->setExchangeOptions(['name' => 'logs-exchange', 'type' => 'topic']);
$producer->publish($argv[1], sprintf('%s.%s', $argv[2], $argv[3]));
