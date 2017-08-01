<?php

require __DIR__ . '/../_rabbitmq.php';

$client = new Thumper\RpcClient($registry->getConnection());
$client->initClient();
$client->addRequest($argv[1], 'charcount', 'charcount'); //the third parameter is the request identifier
echo "Waiting for replies…\n";
$replies = $client->getReplies();

var_dump($replies);
