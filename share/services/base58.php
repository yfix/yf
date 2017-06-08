#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/stephen-hill/base58php.git' => 'base58php/'],
	'autoload_config' => ['base58php/src/' => 'StephenHill'],
	'example' => function() {
		$base58 = new StephenHill\Base58();
		var_dump($base58->encode('Hello World'));
		var_dump($base58->decode('JxF12TrwUP45BMd'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
