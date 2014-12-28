#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/Finder.git' => $libs_root. 'sf_finder/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'sf_finder/' => 'Symfony\Component\Finder',
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
	$finder = new \Symfony\Component\Finder\Finder();
	$iterator = $finder
		->files()
		->name('*.php')
		->depth(0)
		->size('>= 1K')
		->in(__DIR__);

	echo 'list of files with mask *.php inside current dir and in current subdir and size >= 1K'.PHP_EOL;
	foreach ($iterator as $file) {
    	print $file->getRealpath(). PHP_EOL;
	}
}
