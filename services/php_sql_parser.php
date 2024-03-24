#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/yfix/php-sql-parser.git' => 'php_sql_parser/'],
    'autoload_config' => ['php_sql_parser/src/PHPSQLParser/' => 'PHPSQLParser'],
    'example' => function () {
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
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
