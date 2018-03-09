#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/yf_utf8_funcs.git' => 'yf_utf8_funcs/'],
	'manual' => function() {
		$include_path = get_include_path();
		if (false === strpos($include_path, 'yf_utf8_funcs')) {
			set_include_path (YF_PATH.'vendor/yf_utf8_funcs/'. PATH_SEPARATOR. $include_path);
		}
	},
	'example' => function($loader) {
		$str = 'Строка для теста';
		include_once 'utf8_chunk_split.php';
		echo utf8_chunk_split($str, 5, '--');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
