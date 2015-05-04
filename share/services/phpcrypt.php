#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpcrypt.git' => 'phpcrypt/'),
	'autoload_config' => array('phpcrypt/' => 'PHP_Crypt'),
	'require_once' => array('phpcrypt/phpCrypt.php'),
	'example' => function() {
		$obj = new PHP_Crypt\PHP_Crypt('1234567890123456');
		var_dump($obj);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
