#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/avalanche123/Imagine.git' => 'imagine/'),
	'autoload_config' => array('imagine/lib/Imagine/' => 'Imagine'),
	'example' => function() {
		$imagine = new \Imagine\Gd\Imagine();
		var_dump($imagine);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
