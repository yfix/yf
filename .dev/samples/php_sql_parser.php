#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/php-sql-parser.git' => $libs_root. 'php_sql_parser/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'php_sql_parser/src/PHPSQLParser/' => 'PHPSQLParser',
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
/*
require_once $libs_root.'/sf_class_loader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
	'PHPSQLParser' => $libs_root.'/php_sql_parser/src',
));
$loader->register();
*/

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$parser = new \PHPSQLParser\PHPSQLParser();
	$sql = '
		`id` int(6) NOT NULL AUTO_INCREMENT,
		`name` varchar(64) NOT NULL DEFAULT \'\',
		`active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
		PRIMARY KEY (`id`),
		UNIQUE KEY `name` (`name`)
	';
	$parsed = $parser->parse($sql);
	var_export($parsed);
}