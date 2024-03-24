#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/CssSelector.git' => 'sf_css_selector/'],
    'autoload_config' => ['sf_css_selector/' => 'Symfony\Component\CssSelector'],
    'example' => function () {
        echo \Symfony\Component\CssSelector\CssSelector::toXPath('div.item > h4 > a');
        echo PHP_EOL;
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
