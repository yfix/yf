#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/kcfinder.git' => 'kcfinder/'),
	'example' => function($loader) {
		echo (int)file_exists($loader->libs_root.'kcfinder/js_localize.php');
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
