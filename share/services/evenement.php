#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/igorw/evenement.git' => 'evenement/'),
	'autoload_config' => array('evenement/src/Evenement/' => 'Evenement'),
	'example' => function() {
		$emitter = new Evenement\EventEmitter();
		var_dump($emitter);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
