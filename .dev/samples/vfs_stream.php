<?php

# git clone git@github.com:yfix/vfs_stream.git /home/www/yf/libs/vfs_stream/

define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/vfs_stream/src/main/php/';
	$prefix = 'org\bovigo\vfs';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', $class).'.php';
		echo $path. PHP_EOL;
		include $path;
	}
});

$vdir = org\bovigo\vfs\vfsStream::setup('example_dir');
var_dump($vdir);
