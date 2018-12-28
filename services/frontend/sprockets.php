#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/vendethiel/Sprockets-PHP.git' => 'sprockets/'],
    'autoload_config' => ['sprockets/lib/Sprockets/' => 'Sprockets'],
    'example' => function () {
        $pipeline = new Sprockets\Pipeline($paths);
        var_dump($pipeline);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
