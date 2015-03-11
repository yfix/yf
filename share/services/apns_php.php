#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/ApnsPHP.git' => 'apns_php/'),
	'require_once' => array('apns_php/ApnsPHP/Autoload.php'),
	'example' => function($obj) {
		echo (int)class_exists('ApnsPHP_Push_Server');
	}
);
if ($return_config) { return $config; } require __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
