#!/usr/bin/php
<?php

$requires = array('promise');
$git_urls = array(
	'https://github.com/yfix/guzzle.git' => 'guzzle/',
	'https://github.com/yfix/guzzle-ring.git' => 'guzzle-ring/',
	'https://github.com/yfix/guzzle-streams.git' => 'guzzle-streams/',
);
$autoload_config = array(
	'guzzle/src/' => 'GuzzleHttp',
	'guzzle-ring/src/' => 'GuzzleHttp\Ring',
	'guzzle-streams/src/' => 'GuzzleHttp\Stream',
);
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$client = new GuzzleHttp\Client();
	$res = $client->get('http://google.com');
	echo $res->getStatusCode(). PHP_EOL;
	echo $res->getHeader('content-type'). PHP_EOL;
	echo strlen($res->getBody()). PHP_EOL;
}
