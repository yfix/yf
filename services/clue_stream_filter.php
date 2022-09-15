#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/clue/php-stream-filter.git' => 'clue_stream_filter/'],
    'require_once' => ['clue_stream_filter/src/functions.php', 'clue_stream_filter/src/CallbackFilter.php'],
    'example' => function () {
        var_dump(function_exists('Clue\StreamFilter\append'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
