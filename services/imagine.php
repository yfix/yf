#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/avalanche123/Imagine.git' => 'imagine/'],
    'autoload_config' => ['imagine/lib/Imagine/' => 'Imagine'],
    'example' => function () {
        $imagine = new \Imagine\Gd\Imagine();
        var_dump($imagine);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
