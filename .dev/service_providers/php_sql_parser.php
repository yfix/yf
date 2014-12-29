#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/php-sql-parser.git' => 'php_sql_parser/');
$autoload_config = array('php_sql_parser/src/PHPSQLParser/' => 'PHPSQLParser');
require __DIR__.'/_config.php';

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