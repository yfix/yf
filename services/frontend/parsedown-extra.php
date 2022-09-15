#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['parsedown'],
    'git_urls' => ['https://github.com/erusev/parsedown-extra.git' => 'parsedown-extra/'],
    'require_once' => ['parsedown-extra/ParsedownExtra.php'],
    'example' => function () {
        $Extra = new ParsedownExtra();
        echo $Extra->text('# Header {.sth}'); // prints: <h1 class="sth">Header</h1>
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
