#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/erusev/parsedown.git' => 'parsedown/'],
    'require_once' => ['parsedown/Parsedown.php'],
    'example' => function () {
        $Parsedown = new Parsedown();
        echo $Parsedown->text('Hello _Parsedown_!'); // prints: <p>Hello <em>Parsedown</em>!</p>
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
