#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/beberlei/assert.git' => 'beberlei_assert/'],
    'require_once' => ['beberlei_assert/lib/Assert/functions.php'],
    'autoload_config' => ['beberlei_assert/lib/Assert/' => 'Assert'],
    'example' => function () {
        var_dump(class_exists('Assert\Assertion'));
        $res = Assert\Assertion::nullOrMax(null, 42);
        var_dump($res);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
