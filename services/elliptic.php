#!/usr/bin/php
<?php

$config = [
    'require_services' => [
        'bn',
    ],
    'git_urls' => ['https://github.com/simplito/elliptic-php.git' => 'elliptic/'],
    'autoload_config' => ['elliptic/lib/' => 'Elliptic'],
    'example' => function () {
        var_dump(class_exists('\Elliptic\EC'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
