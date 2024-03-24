#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/jmespath/jmespath.php.git' => 'jmespath/'],
    'autoload_config' => ['jmespath/src/' => 'JmesPath'],
    'require_once' => ['jmespath/src/JmesPath.php'],
    'example' => function () {
        var_dump(class_exists('JmesPath\Parser'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
