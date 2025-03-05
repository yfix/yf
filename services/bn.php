#!/usr/bin/php
<?php

$config = [
    'require_services' => [
        'bigint-wrapper',
    ],
    'git_urls' => ['https://github.com/simplito/bn-php.git' => 'bn/'],
    'autoload_config' => ['bn/lib/' => 'BN'],
    'example' => function () {
        var_dump(class_exists('\BN\BN'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
