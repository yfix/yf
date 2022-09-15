#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/Process.git' => 'sf_process/'],
    'autoload_config' => ['sf_process/' => 'Symfony\Component\Process'],
    'example' => function () {
        $process = new Symfony\Component\Process\Process('ls -lsa');
        $process->setTimeout(5);
        $process->run();
        echo $process->getOutput();
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
