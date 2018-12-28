#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/google/google-api-php-client-services.git' => 'google-api-php-client-services/'],
    'pear' => ['google-api-php-client-services/src/' => 'Google_Service_'],
    'example' => function () {
        //		var_dump(class_exists('Google_Service_Translate'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
