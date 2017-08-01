<?php

require __DIR__ . '/../_rabbitmq.php';

$producer = new Thumper\Producer($registry->getConnection());
$producer->setExchangeOptions(['name' => 'hello-exchange', 'type' => 'direct']);
$producer->publish($argv[1]); //The first argument will be the published message
