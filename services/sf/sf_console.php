#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/Console.git' => 'sf_console/'],
    'autoload_config' => ['sf_console/' => 'Symfony\Component\Console'],
    'example' => function () {
        $console = new Symfony\Component\Console\Application();
        $console->run();
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
