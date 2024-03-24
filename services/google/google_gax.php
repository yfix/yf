#!/usr/bin/php
<?php

$config = [
    'require_services' => [
        'google_auth',
        'grpc',
    ],
    'git_urls' => ['https://github.com/googleapis/gax-php.git' => 'google_gax/'],
    'autoload_config' => ['google_gax/src/' => 'Google\GAX'],
    'example' => function () {
        var_dump(class_exists('Google\GAX\Jison\Parser'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
