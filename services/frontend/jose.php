#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'base64url',
		'aes-key-wrap',
		'php-aes-gcm',
		'beberlei_assert',
		'sf_polyfill_mbstring',
		'sf_polyfill_php70',
		'phpasn1',
		'mdanter_ecc',
		'psr_cache',
	],
	'git_urls' => ['https://github.com/Spomky-Labs/jose.git' => 'jose/'],
	'autoload_config' => ['jose/src/' => 'Jose'],
	'example' => function() {
		var_dump(class_exists('Jose\Factory\JWEFactory'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
