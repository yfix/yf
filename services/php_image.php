#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/kus/php-image.git' => 'php_image/'],
    'require_once' => ['php_image/src/PHPImage.php'],
    'example' => function () {
        $image = new PHPImage();
        var_dump($image);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
