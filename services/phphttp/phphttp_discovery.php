#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/php-http/discovery.git' => 'phphttp_discovery/'],
    'autoload_config' => ['phphttp_discovery/src/' => 'Http\Discovery'],
    'example' => function () {
        var_dump(class_exists('Http\Discovery\ClassDiscovery'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
