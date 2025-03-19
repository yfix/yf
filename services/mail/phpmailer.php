#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/PHPMailer/PHPMailer.git~v6.9.3' => 'phpmailer/'],
    'autoload_config' => ['phpmailer/src/' => 'PHPMailer\PHPMailer'],
    'example' => function () {
        var_dump(class_exists('PHPMailer\PHPMailer\PHPMailer'));
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            var_dump($mail);
        } catch (PHPMailer\PHPMailer\Exception $e) {
            print($e->errorMessage()); // Pretty error messages from PHPMailer
        } catch (Exception $e) {
            print($e->getMessage()); // Boring error messages from anything else!
        }
    },
];

if (@$return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
