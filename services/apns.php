#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/ApnsPHP.git' => 'apns/'],
    'require_once' => ['apns/ApnsPHP/Autoload.php'],
    'example' => function () {
        echo (int) class_exists('ApnsPHP_Push_Server');
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
