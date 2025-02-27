#!/usr/bin/php
<?php

$config = [
    // !! Do not upgrade version until checking db unit tests, they broke parsing unique indexes in later versions
    'git_urls' => ['https://github.com/greenlion/PHP-SQL-Parser.git~v4.1.2' => 'php_sql_parser/'],
    // 'git_urls' => ['https://github.com/manticoresoftware/PHP-SQL-Parser.git~v4.6.0-patch10' => 'php_sql_parser/'],
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
if (@$return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
