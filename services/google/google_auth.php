#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'firebase_php_jwt',
		'guzzlehttp_guzzle',
		'guzzlehttp_psr7',
		'psr_http_message',
		'psr_cache',
	],
	'git_urls' => ['https://github.com/google/google-auth-library-php.git' => 'google_auth/'],
	'autoload_config' => ['google_auth/src/' => 'Google\Auth'],
	'example' => function() {
		var_dump(class_exists('Google\Auth\ApplicationDefaultCredentials'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
