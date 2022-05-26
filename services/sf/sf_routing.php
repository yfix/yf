#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/Routing.git' => 'sf_routing/'],
    'autoload_config' => ['sf_routing/' => 'Symfony\Component\Routing'],
    'example' => function () {
        $route = new Symfony\Component\Routing\Route('/hello', ['controller' => 'foo']);
        var_dump($route);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
