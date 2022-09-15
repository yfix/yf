#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/doctrine/cache.git' => 'doctrine_cache/'],
    'autoload_config' => ['doctrine_cache/lib/Doctrine/Common/Cache/' => 'Doctrine\Common\Cache'],
    'example' => function () {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $id = $cache->fetch('some key');
        if ( ! $id) {
            $id = 'something';
            $cache->save('some key', $id);
        }
        echo $cache->fetch('some key') . PHP_EOL;
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
