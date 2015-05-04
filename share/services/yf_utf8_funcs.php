#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/yf_utf8_funcs.git' => 'yf_utf8_funcs/'),
	'example' => function($loader) {
		$str = 'Строка для теста';
		include_once $loader->libs_root.'yf_utf8_funcs/utf8_chunk_split.php';
		echo utf8_chunk_split($str, 5, '--');
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
