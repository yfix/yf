#!/usr/bin/php
<?php

$config = [
    'require_services' => ['random_compat'],
    'git_urls' => ['https://github.com/symfony/polyfill-php70.git' => 'sf_polyfill_php70/'],
    'require_once' => [
        'sf_polyfill_php70/bootstrap.php',
//		'sf_polyfill_php70/Resources/stubs/ArithmeticError.php',
//		'sf_polyfill_php70/Resources/stubs/AssertionError.php',
//		'sf_polyfill_php70/Resources/stubs/DivisionByZeroError.php',
//		'sf_polyfill_php70/Resources/stubs/Error.php',
//		'sf_polyfill_php70/Resources/stubs/ParseError.php',
//		'sf_polyfill_php70/Resources/stubs/TypeError.php',
    ],
    'autoload_config' => ['sf_polyfill_php70/' => 'Symfony\Polyfill\Php70'],
    'example' => function () {
        var_dump(class_exists('Symfony\Polyfill\Php70\Php70'));
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
