#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/swiftmailer.git' => 'swiftmailer/'],
    'require_once' => ['swiftmailer/lib/swift_required.php'],
    'example' => function () {
        $message = Swift_Message::newInstance()
            ->setSubject('Your subject')
            ->setFrom(['john@doe.com' => 'John Doe'])
            ->setTo(['receiver@domain.org', 'other@domain.org' => 'A name'])
            ->setBody('Here is the message itself')
            ->addPart('<q>Here is the message itself</q>', 'text/html');
        var_dump($message);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
