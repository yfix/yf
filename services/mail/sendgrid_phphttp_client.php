#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/sendgrid/php-http-client.git' => 'sendgrid_phphttp_client/'],
    'autoload_config' => ['sendgrid_phphttp_client/lib/' => 'SendGrid'],
    'example' => function () {
        var_dump(class_exists('SendGrid\Client'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
