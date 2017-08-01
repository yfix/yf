<?php

require __DIR__ . '/../_rabbitmq.php';

$charCount = function ($word) {
    sleep(2);
    return strlen($word);
};

$server = new Thumper\RpcServer($registry->getConnection());
$server->initServer('charcount');
$server->setCallback($charCount);
$server->start();
