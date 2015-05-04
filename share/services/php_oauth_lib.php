#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/Lusitanian/PHPoAuthLib.git' => 'php_oauth_lib/'),
	'autoload_config' => array('php_oauth_lib/src/OAuth/' => 'OAuth'),
	'example' => function() {
		$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
		var_dump($uriFactory);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
