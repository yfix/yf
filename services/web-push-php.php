#!/usr/bin/php
<?php

$config = [
    'require_services' => [
        'kriswallsmith_buzz',
        'mdanter_ecc',
        'base64url',
        'php-aes-gcm',
        'jose',
        'guzzlehttp_guzzle',
    ],
    'git_urls' => ['https://github.com/web-push-libs/web-push-php.git' => 'web-push-php/'],
    'autoload_config' => ['web-push-php/src/' => 'Minishlink\WebPush'],
    'example' => function () {
        var_dump(class_exists('Minishlink\WebPush\WebPush'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
