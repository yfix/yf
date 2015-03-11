#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/symfony/Routing.git' => 'sf_routing/'),
	'autoload_config' => array('sf_routing/' => 'Symfony\Component\Routing'),
	'example' => function() {
		$route = new Symfony\Component\Routing\Route('/hello', array('controller' => 'foo'));
		var_dump($route);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
