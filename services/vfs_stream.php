#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/vfsStream.git' => 'vfs_stream/'],
    'autoload_config' => ['vfs_stream/src/main/php/' => 'no_cut_prefix:org\bovigo\vfs'],
    'example' => function () {
        $vdir = org\bovigo\vfs\vfsStream::setup('example_dir');
        var_dump($vdir);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
