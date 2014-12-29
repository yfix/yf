#!/usr/bin/php
<?php

ob_start();
require_once __DIR__.'/sf_event_dispatcher.php';
ob_end_clean();

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';
$git_urls = array(
	'https://github.com/yfix/guzzle.git' => $libs_root. 'guzzle3/',
);
$git_url = key($git_urls);
$lib_dir = current($git_urls);
if (!file_exists($lib_dir.'.git')) {
	$tag = 'v3.7.4';
	$cmd = '(git clone --branch '.$tag.' '.$git_url.' '.$lib_dir.' && cd '.$lib_dir.' && git checkout -b '.$tag.')';
	passthru($cmd);
}
$config = array(
	$libs_root. 'guzzle3/src/Guzzle/' => 'Guzzle',
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
	Guzzle\Http\StaticClient::mount();
	$response = Guzzle::get('http://google.com');
	var_dump($response);
}
