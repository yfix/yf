#!/usr/bin/env php
<?php

$config = [
    'require_services' => [
        'psr_http_message',
        'phphttp_promise',
    ],
    'git_urls' => ['https://github.com/php-http/httplug.git' => 'phphttp_httplug/'],
    'autoload_config' => ['phphttp_httplug/src/' => 'Http\Client'],
    'example' => function () {
        var_dump(interface_exists('Http\Client\HttpClient'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
