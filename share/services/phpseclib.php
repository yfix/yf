#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpseclib.git' => 'phpseclib/'),
	'pear' => array('phpseclib/phpseclib/' => ''),
	'example' => function() {
		$key = new Crypt_RSA();
		var_dump($key);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
