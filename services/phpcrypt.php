#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/phpcrypt.git' => 'phpcrypt/'],
    'autoload_config' => ['phpcrypt/' => 'PHP_Crypt'],
    'require_once' => ['phpcrypt/phpCrypt.php'],
    'example' => function () {
        $obj = new PHP_Crypt\PHP_Crypt('1234567890123456');
        var_dump($obj);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
