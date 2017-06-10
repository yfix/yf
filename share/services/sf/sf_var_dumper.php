#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/var-dumper.git' => 'sf_var_dumper/'],
	'autoload_config' => ['sf_var_dumper/' => 'Symfony\Component\VarDumper'],
	'manual' => function() {
		if (!function_exists('dump')) {
			function dump($var) {
				foreach (func_get_args() as $var) {
					\Symfony\Component\VarDumper\VarDumper::dump($var);
				}
			}
		}
	},
	'example' => function() {
		dump($_SERVER);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
