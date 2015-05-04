#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/packager.git' => 'yf_packager/'),
	'require_once' => array('yf_packager/packager.php'),
	'example' => function() {
		$pkg = new Packager();
		var_dump($pkg);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
