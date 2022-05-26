#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['sendgrid_phphttp_client'],
    'git_urls' => ['https://github.com/sendgrid/sendgrid-php.git' => 'sendgrid/'],
    'require_once' => ['sendgrid/lib/SendGrid.php', 'sendgrid/lib/helpers/mail/Mail.php'],
    'example' => function () {
        var_dump(class_exists('SendGrid\Email'));
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
