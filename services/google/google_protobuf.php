#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/google/protobuf.git' => 'google_protobuf/'],
    'autoload_config' => [
        'google_protobuf/php/src/GPBMetadata/' => 'GPBMetadata',
        'google_protobuf/php/src/Google/Protobuf/' => 'Google\Protobuf',
    ],
    'example' => function () {
        var_dump(class_exists('GPBMetadata\Google\Protobuf\Internal\Descriptor'));
        var_dump(class_exists('Google\Protobuf\Internal\GPBType'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
