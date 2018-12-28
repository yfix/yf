#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/phpmailer.git~v5.2.26' => 'phpmailer/'],
    'require_once' => ['phpmailer/PHPMailerAutoload.php'],
    'example' => function () {
        $mail = new PHPMailer(true);
        var_dump($mail);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
