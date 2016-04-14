#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpredmin.git' => 'phpredmin/'),
	'example' => function($loader) {
		passthru('ls -l '.$loader->libs_root.'/phpredmin/');
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
