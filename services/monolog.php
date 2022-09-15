#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['psr_log'],
    'git_urls' => ['https://github.com/Seldaek/monolog.git' => 'monolog/'],
    'autoload_config' => ['monolog/src/Monolog/' => 'Monolog'],
    'example' => function () {
        $file = '/tmp/test_monolog.log';
        file_exists($file) && unlink($file);

        // create a log channel
        $log = new Monolog\Logger('name');
        $log->pushHandler(new Monolog\Handler\StreamHandler($file, Monolog\Logger::WARNING));

        // add records to the log
        $log->addWarning('Foo');
        $log->addError('Bar');

        echo file_get_contents($file);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
