#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://bitbucket.org/mailchimp/mandrill-api-php.git' => 'mandrill/'],
    'require_once' => ['mandrill/src/Mandrill.php'],
    'example' => function () {
        $client = new Mandrill('Your api key');
        var_dump($client);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
