#!/usr/bin/env php
<?php

$config = [
    'require_services' => [
        'google_auth',
        'google_api_php_client_services',
        'firebase_php_jwt',
        'monolog',
        'phpseclib',
        'guzzlehttp_guzzle',
        'guzzlehttp_psr7',
    ],
    'git_urls' => ['https://github.com/google/google-api-php-client.git' => 'google_api_client/'],
    'pear' => ['google_api_client/src/' => 'Google_'],
    'example' => function () {
        var_dump(class_exists('Google_Service_Translate'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
