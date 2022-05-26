#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/geonames-db-import.git' => 'yf_geonames_db_import/'],
    'example' => function ($loader) {
        passthru('ls -Rl ' . $loader->libs_root . 'yf_geonames_db_import/');
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
