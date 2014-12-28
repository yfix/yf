<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');

$libs_root = YF_PATH.'libs';
require_once $libs_root.'/sf_class_loader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
	'PHPSQLParser' => $libs_root.'/php_sql_parser/src',
));
$loader->register();
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