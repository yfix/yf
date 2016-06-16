#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpseclib.git' => 'phpseclib/'),
	'autoload_config' => array('phpseclib/phpseclib/' => 'phpseclib'),
	'example' => function() {
		$aes = new \phpseclib\Crypt\AES();
		$aes->setKey('abcdefghijklmnop');
		var_dump($aes);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
