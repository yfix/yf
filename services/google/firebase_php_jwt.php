#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/firebase/php-jwt.git' => 'firebase_php_jwt/'],
    'autoload_config' => ['firebase_php_jwt/src/' => 'Firebase\JWT'],
    'example' => function () {
        $key = 'example_key';
        $token = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'iat' => 1356999524,
            'nbf' => 1357000000,
        ];
        $jwt = \Firebase\JWT\JWT::encode($token, $key);
        $decoded = \Firebase\JWT\JWT::decode($jwt, $key, ['HS256']);
        print_r($decoded);

        $decoded_array = (array) $decoded;

        \Firebase\JWT\JWT::$leeway = 60; // $leeway in seconds
        $decoded = \Firebase\JWT\JWT::decode($jwt, $key, ['HS256']);
        print_r($decoded);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
