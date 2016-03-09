#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/minify.git' => 'minify/'),
	'require_once' => array('minify/min/lib/Minify.php'),
	'example' => function() {
		var_dump(class_exists('Minify'));
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
