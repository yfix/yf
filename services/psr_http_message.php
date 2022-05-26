#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/php-fig/http-message.git' => 'http-message/'],
    'autoload_config' => ['http-message/src/' => 'Psr\Http\Message'],
    'example' => function () {
        var_dump(interface_exists('Psr\Http\Message\UriInterface'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
