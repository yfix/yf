#!/usr/bin/env php
<?php

$config = [
    'require_services' => [
        'sf_console',
        'sf_finder',
        'sf_filesystem',
        'sf_process',
        'php_semver',
        'php_github_api',
        'json_pretty',
    ],
    'git_urls' => ['https://github.com/Bee-Lab/bowerphp.git' => 'bowerphp/'],
    'autoload_config' => ['bowerphp/src/Bowerphp/' => 'Bowerphp'],
    'example' => function () {
        $input = new Symfony\Component\Console\Input\ArrayInput(['command' => 'info', 'package' => 'jquery']);
        $application = new Bowerphp\Console\Application();
        $application->setAutoExit(false);
        $application->run($input);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
