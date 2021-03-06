#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/jQuery-File-Upload.git' => 'jquery-file-upload/'],
    'require_once' => ['jquery-file-upload/server/php/UploadHandler.php'],
    'example' => function () {
        echo (int) class_exists('UploadHandler');
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
