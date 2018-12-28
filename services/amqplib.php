#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/php-amqplib/php-amqplib.git' => 'amqplib/'],
    'autoload_config' => ['amqplib/PhpAmqpLib/' => 'PhpAmqpLib'],
    'example' => function () {
        $host = 'localhost';
        $port = '5672';
        $login = 'guest';
        $pswd = 'guest';
        $connection = new PhpAmqpLib\Connection\AMQPConnection($host, $port, $login, $pswd);
        var_dump($connection);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
