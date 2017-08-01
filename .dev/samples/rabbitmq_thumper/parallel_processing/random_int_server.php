<?php

require __DIR__ . '/../_rabbitmq.php';

$randomInt = function ($data) {
    sleep(5);
    $data = unserialize($data);
    return rand($data['min'], $data['max']);
};

$server = new Thumper\RpcServer($registry->getConnection());
$server->initServer('random-int');
$server->setCallback($randomInt);
$server->start();
