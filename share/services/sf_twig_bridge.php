#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('twig'),
	'git_urls' => array('https://github.com/symfony/TwigBridge.git' => 'sf_twig_bridge/'),
	'autoload_config' => array('sf_twig_bridge/' => 'Symfony\Bridge\Twig'),
	'example' => function($loader) {
		passthru('ls -l '.$loader->libs_root.'sf_twig_bridge/');
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
