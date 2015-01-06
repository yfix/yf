#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/dropbox/dropbox-sdk-php.git' => 'dropbox-sdk-php/');
$autoload_config = array('dropbox-sdk-php/lib/Dropbox/' => 'Dropbox');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$appInfo = \Dropbox\AppInfo::loadFromJson(json_decode('{"key": "INSERT_APP_KEY_HERE", "secret": "INSERT_SECRET_HERE"}', true));
	$webAuth = new \Dropbox\WebAuthNoRedirect($appInfo, 'PHP-Example/1.0');
	var_dump($webAuth);
}
