#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/options-resolver.git' => 'sf_options_resolver/'],
    'autoload_config' => ['sf_options_resolver/' => 'Symfony\Component\OptionsResolver'],
    'example' => function () {
        var_dump(class_exists('Symfony\Component\OptionsResolver\OptionsResolver'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
