#!/usr/bin/php
<?php

$config = [
    'git_urls' => [ 'https://github.com/symfony/deprecation-contracts.git' => 'sf_dc/' ],
    'require_once' => [ 'sf_dc/function.php' ],
    'example' => function () {
        trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', 'example_test');
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
