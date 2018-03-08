#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/php-fig/cache.git' => 'psr_cache/'],
	'autoload_config' => ['psr_cache/src/' => 'Psr\Cache'],
	'example' => function() {
		var_dump(interface_exists('Psr\Cache\CacheItemInterface'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
