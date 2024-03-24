#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/composer/semver.git' => 'composer_semver/'],
    'autoload_config' => ['composer_semver/src/' => 'Composer\Semver'],
    'example' => function () {
        var_dump(Composer\Semver\Comparator::greaterThan('1.25.0', '1.24.0')); // 1.25.0 > 1.24.0
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
