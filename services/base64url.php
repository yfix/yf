#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/Spomky-Labs/base64url.git' => 'base64url/'],
    'autoload_config' => ['base64url/src/' => 'Base64Url'],
    'example' => function () {
        var_dump(class_exists('Base64Url\Base64Url'));
        $message = 'Hello World!';
        $encoded = Base64Url\Base64Url::encode($message); //Result must be "SGVsbG8gV29ybGQh"
        echo $encoded . PHP_EOL;
        $decoded = Base64Url\Base64Url::decode($encoded); //Result must be "Hello World!"
        echo $decoded . PHP_EOL;
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
