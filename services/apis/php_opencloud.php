#!/usr/bin/php
<?php

$config = [
    'require_services' => ['guzzle3'],
    'git_urls' => ['https://github.com/rackspace/php-opencloud.git' => 'php-opencloud/'],
    'autoload_config' => ['php-opencloud/lib/OpenCloud/' => 'OpenCloud'],
    'example' => function () {
        $client = new \OpenCloud\Rackspace(\OpenCloud\Rackspace::US_IDENTITY_ENDPOINT, [
            'username' => 'foo',
            'apiKey' => 'bar',
        ]);
        var_dump($client);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
