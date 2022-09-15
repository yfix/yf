#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/webmozart/assert.git' => 'webmozart_assert/'],
    'autoload_config' => ['webmozart_assert/src/' => 'Webmozart\Assert'],
    'example' => function () {
        var_dump(class_exists('Webmozart\Assert\Assert'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
