#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/igorw/evenement.git' => 'evenement/'],
    'autoload_config' => ['evenement/src/Evenement/' => 'Evenement'],
    'example' => function () {
        $emitter = new Evenement\EventEmitter();
        var_dump($emitter);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
