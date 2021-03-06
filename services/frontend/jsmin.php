#!/usr/bin/php
<?php

$config = [
    'require_services' => ['minify'],
    'require_once' => ['minify/min/lib/JSMin.php'],
    'example' => function () {
        $js = ' function  hello_world ( i , v ) { return " " ; } ';
        var_dump($js);
        $min = \JSMin::minify($js);
        var_dump($min);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
