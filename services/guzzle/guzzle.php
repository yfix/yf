#!/usr/bin/php
<?php

$config = [
	'require_services' => ['promise'],
	'git_urls' => [
		'https://github.com/yfix/guzzle.git' => 'guzzle/',
		'https://github.com/yfix/guzzle-ring.git' => 'guzzle-ring/',
		'https://github.com/yfix/guzzle-streams.git' => 'guzzle-streams/',
	],
	'autoload_config' => [
		'guzzle/src/' => 'GuzzleHttp',
		'guzzle-ring/src/' => 'GuzzleHttp\Ring',
		'guzzle-streams/src/' => 'GuzzleHttp\Stream',
	],
	'example' => function() {
		$client = new GuzzleHttp\Client();
		$res = $client->get('http://google.com');
		echo $res->getStatusCode(). PHP_EOL;
		echo $res->getHeader('content-type'). PHP_EOL;
		echo strlen($res->getBody()). PHP_EOL;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
