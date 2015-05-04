#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/vendethiel/Sprockets-PHP.git' => 'sprockets_php/'),
	'autoload_config' => array('sprockets_php/lib/Sprockets/' => 'Sprockets'),
	'example' => function() {
		$pipeline = new Sprockets\Pipeline($paths);
		var_dump($pipeline);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
