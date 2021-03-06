#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/php-unified-archive.git' => 'php-unified-archive/'],
    'autoload_config' => ['php-unified-archive/src/' => 'wapmorgan\UnifiedArchive'],
    'example' => function () {
        $out = \wapmorgan\UnifiedArchive\UnifiedArchive::archiveNodes('./', 'samples_archive.zip', $fake = true);
        var_export($out);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
