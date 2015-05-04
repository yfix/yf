#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('sf_event_dispatcher'),
	'git_urls' => array('https://github.com/yfix/guzzle.git~v3.7.4' => 'guzzle3/'),
	'autoload_config' => array('guzzle3/src/Guzzle/' => 'Guzzle'),
	'example' => function() {
		Guzzle\Http\StaticClient::mount();
		$response = Guzzle::get('http://google.com');
		echo strlen($response);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
