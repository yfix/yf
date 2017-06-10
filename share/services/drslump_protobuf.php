#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/drslump/Protobuf-PHP.git' => 'drslump_protobuf/'],
	'autoload_config' => ['drslump_protobuf/library/DrSlump/' => 'DrSlump'],
	'example' => function() {
		var_dump(class_exists('DrSlump\Protobuf'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
