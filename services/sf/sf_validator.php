#!/usr/bin/env php
<?php

$config = [
    'require_services' => ['sf_translation'],
    'git_urls' => ['https://github.com/symfony/Validator.git' => 'sf_validator/'],
    'autoload_config' => ['sf_validator/' => 'Symfony\Component\Validator'],
    'example' => function () {
        $validator = \Symfony\Component\Validator\Validation::createValidator();
        $violations = $validator->validateValue('Bernhard', new \Symfony\Component\Validator\Constraints\Length(['min' => 10]));
        var_dump($violations);
    },
];
if ($return_config) {
    return $config;
}
require_once __DIR__ . '/_yf_autoloader.php';
new yf_autoloader($config);
