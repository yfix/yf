#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/composer/ca-bundle.git' => 'composer_ca_bundle/'],
    'autoload_config' => ['composer_ca_bundle/src/' => 'Composer\CaBundle'],
    'example' => function () {
        var_dump(class_exists('\Composer\CaBundle\CaBundle'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
