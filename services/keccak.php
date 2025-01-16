#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/kornrunner/php-keccak.git' => 'keccak/'],
    'autoload_config' => ['keccak/src/' => 'kornrunner'],
    'example' => function () {
        var_dump(class_exists('\kornrunner\Keccak'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
