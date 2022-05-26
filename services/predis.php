#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/nrk/predis.git' => 'predis/'],
    'autoload_config' => ['predis/src/' => 'Predis'],
    'example' => function () {
        $client = new Predis\Client([
            'scheme' => 'tcp',
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?: 6379,
        ]);
        $client->set('testme_key', 'testme_val');
        $value = $client->get('testme_key');
        echo($value == 'testme_val' ? 'OK' : 'ERROR') . PHP_EOL;
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
