#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/twbs/bootstrap.git' => 'twitter-bootstrap/'],
    'example' => function ($loader) {
        passthru('ls -lR ' . $loader->libs_root . 'twitter-bootstrap/less/');
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
