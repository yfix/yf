#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/justinrainbow/json-schema.git' => 'json_schema/'],
    'autoload_config' => ['json_schema/src/JsonSchema/' => 'JsonSchema'],
    'example' => function () {
        $retriever = new JsonSchema\Uri\UriRetriever();
        var_dump($retriever);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
