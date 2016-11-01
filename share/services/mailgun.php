#!/usr/bin/php
<?php

$config = [
	'require_services' => [/* 'guzzlehttp_psr7', */'phphttp_httplug', 'phphttp_discovery', 'phphttp_multipart_stream_builder', 'phphttp_message',
		'phphttp_client_common',
		'webmozart_assert',
	],
	'git_urls' => ['https://github.com/mailgun/mailgun-php.git' => 'mailgun/'],
	'autoload_config' => ['mailgun/src/Mailgun/' => 'Mailgun'],
	'example' => function() {
		$mg_client = new Mailgun\Mailgun('example key');
		var_dump($mg_client);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
