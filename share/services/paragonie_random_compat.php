#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/paragonie/random_compat.git' => 'paragonie_random_compat/'],
	'require_once' => [
		'paragonie_random_compat/lib/random.php',
	],
	'example' => function() {
		var_dump(random_int(0, 255));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
