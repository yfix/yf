#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/phpseclib/phpseclib.git~1.0' => 'phpseclib_v1/'],
    'pear' => ['phpseclib_v1/phpseclib/' => ''],
    'example' => function () {
        $key = new Crypt_RSA();
        var_dump($key);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
