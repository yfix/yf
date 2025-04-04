#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/simplito/bigint-wrapper-php.git' => 'bigint-wrapper/'],
    'autoload_config' => ['bigint-wrapper/lib/' => 'BI'],
    'example' => function () {
        var_dump(class_exists('\BI\BigInteger'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
