#!/usr/bin/php
<?php

$config = [
	'require_services' => ['twig'],
	'git_urls' => ['https://github.com/symfony/TwigBridge.git' => 'sf_twig_bridge/'],
	'autoload_config' => ['sf_twig_bridge/' => 'Symfony\Bridge\Twig'],
	'example' => function($loader) {
		passthru('ls -l '.$loader->libs_root.'sf_twig_bridge/');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
