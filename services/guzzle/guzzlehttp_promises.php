#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/guzzle/promises.git' => 'guzzlehttp_promises/'],
    'autoload_config' => ['guzzlehttp_promises/src/' => 'GuzzleHttp\Promise'],
    'require_once' => ['guzzlehttp_promises/src/functions_include.php'],
    'example' => function () {
        var_dump(class_exists('GuzzleHttp\Promise\Promise'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
