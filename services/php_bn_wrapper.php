#!/usr/bin/php
<?php

// BigInteger wrapper library for PHP

$config = [
    'git_urls' => [ 'https://github.com/simplito/bigint-wrapper-php.git' => 'php_bigint_wrapper/' ],
    'autoload_config' => [ 'php_bigint_wrapper/lib/' => 'BI', ],
    'example' => function () {
        var_dump( class_exists( 'BigInteger' ) );
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
