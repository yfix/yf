#!/usr/bin/php
<?php

// BigNum library for PHP

$config = [
    'require_services' => [
        'php_bn_wrapper',
    ],
    'git_urls' => [ 'https://github.com/simplito/bn-php.git' => 'php_bn/' ],
    'autoload_config' => [ 'php_bn/lib/' => 'BN', ],
    'example' => function () {
        var_dump( class_exists( 'Elliptic\EC' ) );
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
