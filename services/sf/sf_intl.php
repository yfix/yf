#!/usr/bin/env php
<?php

$config = [
    'git_urls' => ['https://github.com/symfony/Intl.git' => 'sf_intl/'],
    'autoload_config' => ['sf_intl/' => 'Symfony\Component\Intl'],
    'example' => function () {
        $languages = \Symfony\Component\Intl\Intl::getLanguageBundle()->getLanguageNames();
        var_dump($languages);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
