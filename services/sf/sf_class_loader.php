#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/ClassLoader.git' => 'sf_class_loader/'],
    'autoload_config' => ['sf_class_loader/' => 'Symfony\Component\ClassLoader'],
    'example' => function () {
        $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
        var_dump($loader);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
