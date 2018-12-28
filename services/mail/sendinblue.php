#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/mailin-api/mailin-api-php.git' => 'sendinblue/'],
    'require_once' => ['sendinblue/src/Sendinblue/Mailin.php'],
    'example' => function () {
        var_dump(class_exists('Sendinblue\Mailin'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
