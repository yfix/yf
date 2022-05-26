#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['amqplib'],
    'git_urls' => ['https://github.com/php-amqplib/Thumper.git' => 'thumper/'],
    'autoload_config' => ['thumper/lib/Thumper/' => 'Thumper'],
    'example' => function () {
        var_dump(class_exists('Thumper\RpcClient'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
