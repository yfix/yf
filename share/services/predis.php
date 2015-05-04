#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/nrk/predis.git' => 'predis/'),
	'autoload_config' => array('predis/src/' => 'Predis'),
	'example' => function() {
		$client = new Predis\Client(array(
		    'scheme' => 'tcp',
		    'host'   => '127.0.0.1',
    		'port'   => 6379,
		));
		$client->set('foo', 'bar');
		$value = $client->get('foo');
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
