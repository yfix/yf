#!/usr/bin/php
<?php

// Pure PHP implementation of Keccak (SHA-3)

$config = [
    'require_services' => [
        'sf_polyfill_mbstring',
    ],
    'git_urls' => [ 'https://github.com/kornrunner/php-keccak.git' => 'php_keccak/' ],
    'autoload_config' => [ 'php_keccak/src/' => 'kornrunner', ],
    'example' => function () {
        var_dump( class_exists( 'kornrunner\Keccak' ) );
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
