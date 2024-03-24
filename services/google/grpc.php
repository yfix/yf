#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/grpc/grpc.git' => 'grpc/'],
    'autoload_config' => ['grpc/src/php/lib/Grpc/' => 'Grpc'],
    'example' => function () {
        var_dump(class_exists('Grpc\AbstractCall'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
