#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/dropbox/dropbox-sdk-php.git' => 'dropbox-sdk-php/'],
	'autoload_config' => ['dropbox-sdk-php/lib/Dropbox/' => 'Dropbox'],
	'example' => function() {
		$appInfo = \Dropbox\AppInfo::loadFromJson(json_decode('{"key": "INSERT_APP_KEY_HERE", "secret": "INSERT_SECRET_HERE"}', true));
		$webAuth = new \Dropbox\WebAuthNoRedirect($appInfo, 'PHP-Example/1.0');
		var_dump($webAuth);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
