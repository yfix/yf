#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/Filesystem.git' => 'sf_filesystem/'],
    'autoload_config' => ['sf_filesystem/' => 'Symfony\Component\Filesystem'],
    'example' => function () {
        $filesystem = new Symfony\Component\Filesystem\Filesystem();
        $res = $filesystem->exists(__FILE__);
        var_dump($res);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
