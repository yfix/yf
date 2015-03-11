#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpseclib.git' => 'phpseclib/'),
	// TODO: implement pear-style autoload
	// $pear = array('phpseclib/phpseclib/' => 'phpseclib');
	'manual' => function($loader) {
		set_include_path($loader->libs_root. 'phpseclib/phpseclib/'. PATH_SEPARATOR. get_include_path());
		require_once 'Crypt/RSA.php';
		require_once 'Net/SSH2.php';
	},
	'example' => function() {
		$key = new Crypt_RSA();
		var_dump($key);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
