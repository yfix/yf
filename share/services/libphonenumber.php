#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/libphonenumber-for-php.git' => 'libphonenumber/'),
	'autoload_config' => array('libphonenumber/src/libphonenumber/' => 'libphonenumber'),
	'example' => function() {
		$swissNumberStr = '044 668 18 00';
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$swissNumberProto = $phoneUtil->parse($swissNumberStr, 'CH');
			var_dump($swissNumberProto);
		} catch (\libphonenumber\NumberParseException $e) {
			var_dump($e);
		}
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
