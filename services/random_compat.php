#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/paragonie/random_compat.git' => 'random_compat/'],
	'require_once' => ['random_compat/lib/random.php'],
	'example' => function() {
		var_dump(bin2hex(random_bytes(32)));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
