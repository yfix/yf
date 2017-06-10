#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'beberlei_assert',
		'sf_polyfill_mbstring',
	],
	'git_urls' => ['https://github.com/Spomky-Labs/aes-key-wrap.git' => 'aes-key-wrap/'],
	'autoload_config' => ['aes-key-wrap/src/' => 'AESKW'],
	'example' => function() {
		var_dump(class_exists('AESKW\A128KW'));
		// The Key Encryption Key
		$kek  = hex2bin('000102030405060708090A0B0C0D0E0F');
		// The key we want to wrap
		$key  = hex2bin('00112233445566778899AABBCCDDEEFF');
		// We wrap the key
		$wrapped_key = AESKW\A128KW::wrap($kek, $key); // Must return "1FA68B0A8112B447AEF34BD8FB5A7B829D3E862371D2CFE5"
		// We unwrap the key
		$unwrapped_key = AESKW\A128KW::unwrap($kek, $wrapped_key); // The result must be the same value as the key
		var_dump($unwrapped_key === $key);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
