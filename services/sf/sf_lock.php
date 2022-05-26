#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/lock.git' => 'sf_lock/'],
    'autoload_config' => ['sf_lock/' => 'Symfony\Component\Lock'],
    'example' => function () {
        echo (int) class_exists('\Symfony\Component\Lock\Key');
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
