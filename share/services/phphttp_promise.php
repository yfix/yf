#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/php-http/promise.git' => 'phphttp_promise/'],
	'autoload_config' => ['phphttp_promise/src/' => 'Http\Promise'],
	'example' => function() {
		var_dump(interface_exists('Http\Promise\Promise'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
