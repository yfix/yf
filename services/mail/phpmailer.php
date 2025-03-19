#!/usr/bin/php
<?php

$config = [
//    'git_urls' => ['https://github.com/yfix/phpmailer.git~v5.2.26' => 'phpmailer/'],
    'git_urls' => ['https://github.com/PHPMailer/PHPMailer.git' => 'phpmailer/'],
    //'require_once' => ['phpmailer/PHPMailerAutoload.php'],
    'autoload_config' => ['phpmailer/src/' => 'PHPMailer\PHPMailer'],
    'example' => function () {
        /*
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\SMTP;
        use PHPMailer\PHPMailer\Exception;
        $mail = new PHPMailer(true);
        var_dump($mail);
         */
        var_dump(class_exists('PHPMailer\PHPMailer\PHPMailer'));
    },
];

if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
