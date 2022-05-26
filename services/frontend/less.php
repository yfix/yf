#!/usr/bin/env php
<?php

$config = [
    'composer_names' => ['leafo/lessphp'],
    'example' => function () {
        $less = new \lessc();
        echo $less->compile('.block { padding: 3 + 4px }');
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
