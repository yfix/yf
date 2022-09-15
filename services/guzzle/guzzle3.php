#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['sf_event_dispatcher'],
    'git_urls' => ['https://github.com/yfix/guzzle.git~v3.7.4' => 'guzzle3/'],
    'autoload_config' => ['guzzle3/src/Guzzle/' => 'Guzzle'],
    'example' => function () {
        Guzzle\Http\StaticClient::mount();
        $response = Guzzle::get('http://google.com');
        echo strlen($response);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
