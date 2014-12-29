#!/usr/bin/php
<?php

ob_start();
require_once __DIR__.'/sf_filesystem.php';
ob_end_clean();

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/symfony/Config.git' => $libs_root. 'sf_config/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'sf_config/' => 'Symfony\Component\Config',
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
	$treeBuilder = new \Symfony\Component\Config\Definition\Builder\TreeBuilder();
	$rootNode = $treeBuilder->root('database');
	$rootNode
		->children()
			->enumNode('gender')
				->values(array('male', 'female'))
			->end()
		->end();
	var_dump($treeBuilder);
}
