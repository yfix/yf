#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/Propel2.git' => $libs_root. 'propel2/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'propel2/src/Propel/' => 'Propel',
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
	$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
	$serviceContainer->setAdapterClass('bookstore', 'mysql');
	$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
	$manager->setConfiguration(array (
		'dsn'      => 'mysql:host=localhost;dbname=yf_for_unit_tests',
		'user'     => 'root',
		'password' => '123456',
	));
	$serviceContainer->setConnectionManager('bookstore', $manager);
	var_dump($manager);
}
