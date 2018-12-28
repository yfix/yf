#!/usr/bin/php
<?php

$config = [
    'require_services' => ['sf_dom_crawler'],
    'git_urls' => ['https://github.com/yfix/BrowserKit.git' => 'sf_browserkit/'],
    'autoload_config' => ['sf_browserkit/' => 'Symfony\Component\BrowserKit'],
    'example' => function () {
        $history = new \Symfony\Component\BrowserKit\History();
        var_dump($history);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
