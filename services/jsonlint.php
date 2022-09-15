#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/Seldaek/jsonlint.git' => 'jsonlint/'],
    'autoload_config' => ['jsonlint/src/Seld/JsonLint/' => 'Seld\JsonLint'],
    'example' => function () {
        $parser = new Seld\JsonLint\JsonParser();
        $json = '{"Hello":"World"}';
        $out = $parser->parse($json);
        var_dump($out);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
