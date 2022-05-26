#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/colinmollenhour/credis.git' => 'credis/'],
    'require_once' => ['credis/Client.php', 'credis/Cluster.php', 'credis/Sentinel.php'],
    'example' => function () {
        $client = new Credis_Client(getenv('REDIS_HOST') ?: '127.0.0.1', getenv('REDIS_PORT') ?: 6379);
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
