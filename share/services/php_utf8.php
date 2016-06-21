#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/php5-utf8.git' => 'php5-utf8/'],
	'require_once' => [
		'php5-utf8/ReflectionTypeHint.php',
		'php5-utf8/UTF8.php',
	],
	'example' => function() {
		$str = 'Строка для теста';
		echo UTF8::chunk_split($str, 5, '--');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
