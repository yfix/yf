#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/HttpFoundation.git' => 'sf_http_foundation/'],
    'autoload_config' => ['sf_http_foundation/' => 'Symfony\Component\HttpFoundation'],
    'example' => function () {
        $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
        echo $request->getPathInfo();
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
