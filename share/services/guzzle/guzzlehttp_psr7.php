#!/usr/bin/php
<?php

$config = [
	'require_services' => ['psr_http_message'],
	'git_urls' => ['https://github.com/guzzle/psr7.git' => 'guzzlehttp_psr7/'],
	'require_once' => ['guzzlehttp_psr7/src/functions_include.php'],
	'autoload_config' => ['guzzlehttp_psr7/src/' => 'GuzzleHttp\Psr7'],
	'example' => function() {
		$a = GuzzleHttp\Psr7\stream_for('abc, ');
		var_dump($a);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
