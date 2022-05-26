#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/briannesbitt/Carbon.git' => 'carbon/'],
    'autoload_config' => ['carbon/src/Carbon/' => 'Carbon'],
    'example' => function () {
        $diff = Carbon\Carbon::now()->subDays(5)->diffForHumans();
        echo $diff . PHP_EOL;
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
