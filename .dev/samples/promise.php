<?php

# git clone git@github.com:yfix/promise.git /home/www/yf/libs/promise/
define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/promise/src/';
	$prefix = 'React\Promise';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
		echo $path.PHP_EOL;
		include $path;
	}
});

$deferred = new React\Promise\Deferred();
$promise = $deferred->promise();
var_dump($promise);
