#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/symfony/polyfill-mbstring.git' => 'sf_polyfill_mbstring/'],
	'require_once' => ['sf_polyfill_mbstring/bootstrap.php'],
	'autoload_config' => ['sf_polyfill_mbstring/' => 'Symfony\Polyfill\Mbstring'],
	'example' => function() {
		var_dump(class_exists('Symfony\Polyfill\Mbstring\Mbstring'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
