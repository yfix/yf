#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/rlerdorf/opcache-status.git' => 'opcache-status/'],
    'example' => function ($loader) {
        passthru('ls -l ' . $loader->libs_root . '/opcache-status/');
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
