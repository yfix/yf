#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/symfony/Debug.git' => 'sf_debug/'],
	'autoload_config' => ['sf_debug/' => 'Symfony\Component\Debug'],
	'example' => function() {
		echo (int)class_exists('\Symfony\Component\Debug\ErrorHandler');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
