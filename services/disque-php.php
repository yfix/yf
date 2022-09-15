#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/disque-php.git' => 'disque-php/'],
    'autoload_config' => ['disque-php/src/' => 'Disque'],
    'example' => function () {
        var_dump(class_exists('Disque\Connection\Credentials'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
