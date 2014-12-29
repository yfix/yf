#!/usr/bin/php
<?php

ob_start();
require_once __DIR__.'/promise.php';
ob_end_clean();

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';
$git_urls = array(
	'https://github.com/yfix/guzzle.git' => $libs_root. 'guzzle/',
	'https://github.com/yfix/guzzle-ring.git' => $libs_root. 'guzzle-ring/',
	'https://github.com/yfix/guzzle-streams.git' => $libs_root. 'guzzle-streams/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'guzzle/src/' => 'GuzzleHttp',
	$libs_root. 'guzzle-ring/src/' => 'GuzzleHttp\Ring',
	$libs_root. 'guzzle-streams/src/' => 'GuzzleHttp\Stream',
);
spl_autoload_register(function($class) use ($config) {
#	echo '=='.$class .PHP_EOL;
	foreach ($config as $lib_root => $prefix) {
		if (strpos($class, $prefix) !== 0) {
			continue;
		}
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
		if (!file_exists($path)) {
			continue;
		}
#		echo $path.PHP_EOL;
		include $path;
		return true;
	}
});

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$client = new GuzzleHttp\Client();
	$res = $client->get('http://google.com');
	echo $res->getStatusCode(). PHP_EOL;
	echo $res->getHeader('content-type'). PHP_EOL;
	echo $res->getBody(). PHP_EOL;
}
