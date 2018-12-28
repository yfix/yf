#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/coldume/imagecraft.git' => 'imagecraft/'],
    'autoload_config' => ['imagecraft/src/' => 'Imagecraft'],
    'example' => function () {
        $options = ['engine' => 'php_gd'];
        $builder = new Imagecraft\ImageBuilder($options);
        $context = $builder->about();
        $res = $context->isEngineSupported();
        var_dump($res);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
