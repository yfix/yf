#!/usr/bin/php
<?php

// Fast Elliptic Curve Cryptography in PHP

$config = [
    'require_services' => [
        'php_bn',
    ],
    'git_urls' => [ 'https://github.com/simplito/elliptic-php.git' => 'php_elliptic/' ],
    'autoload_config' => [ 'php_elliptic/lib/' => 'Elliptic', ],
    'example' => function () {
        var_dump( class_exists( 'EC' ) );
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
