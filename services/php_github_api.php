#!/usr/bin/php
<?php

$config = [
    'require_services' => ['guzzle3'],
    'git_urls' => ['https://github.com/KnpLabs/php-github-api.git' => 'php_github_api/'],
    'autoload_config' => ['php_github_api/lib/Github/' => 'Github'],
    'example' => function () {
        $client = new \Github\Client();
        $repositories = $client->api('user')->repositories('yfix');
        foreach ($repositories as $v) {
            $a[$v['full_name']] = $v['html_url'];
        }
        print_r($a);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
