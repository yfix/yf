#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/krichprollsch/phpCssEmbed.git' => 'cssembed/'],
    'autoload_config' => ['cssembed/src/CssEmbed/' => 'CssEmbed'],
    'example' => function () {
        $css = '.test { display: none; }';
        $pce = new \CssEmbed\CssEmbed();
        echo $pce->embedString($css);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
