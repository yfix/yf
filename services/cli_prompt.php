#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/Seldaek/cli-prompt.git' => 'cli_prompt/'],
    'autoload_config' => ['cli_prompt/src/' => 'Seld\CliPrompt'],
    'example' => function () {
        var_dump(class_exists('\Seld\CliPrompt\CliPrompt'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
