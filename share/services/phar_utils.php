#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/Seldaek/phar-utils.git' => 'phar_utils/'],
	'autoload_config' => ['phar_utils/src/' => 'Seld\PharUtils'],
	'example' => function() {
		var_dump(class_exists('\Seld\PharUtils\Timestamps'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
