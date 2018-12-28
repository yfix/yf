#!/usr/bin/php
<?php

$config = [
    'require_services' => [
        'guzzlehttp_psr7',
        'guzzlehttp_promises',
    ],
    'git_urls' => ['https://github.com/guzzle/guzzle.git' => 'guzzlehttp_guzzle/'],
    'require_once' => ['guzzlehttp_guzzle/src/functions_include.php'],
    'autoload_config' => ['guzzlehttp_guzzle/src/' => 'GuzzleHttp'],
    'example' => function () {
        var_dump(class_exists('GuzzleHttp\Client'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
