#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/flysystem.git' => 'flysystem/'],
    'autoload_config' => ['flysystem/src/' => 'League\Flysystem'],
    'example' => function () {
        $filesystem = new League\Flysystem\Filesystem(new League\Flysystem\Adapter\Local(__DIR__));
        foreach ($filesystem->listContents() as $p) {
            echo $p['path'] . PHP_EOL;
        }
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
