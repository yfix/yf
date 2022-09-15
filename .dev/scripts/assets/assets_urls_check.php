#!/usr/bin/env php
<?php

$data = require __DIR__ . '/assets_urls_collect.php';

function get_url_size($url)
{
    if (substr($url, 0, 2) === '//') {
        $url = 'http:' . $url;
    }
    return strlen(file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]])));
}

$COLOR_GREEN = "\033[32m";
$COLOR_RED = "\033[31m";
$COLOR_END = "\033[0m";

foreach ($data['urls'] as $url) {
    $size = get_url_size($url);
    foreach ($data['paths'][$url] as $path) {
        echo ($size > 50 ? $COLOR_GREEN . 'GOOD' . $COLOR_END : $COLOR_RED . 'BAD' . $COLOR_END)
            . ' | ' . $url . ' | ' . $path . ' | ' . $size . PHP_EOL;
    }
}
