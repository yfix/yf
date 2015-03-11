#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/symfony/HttpFoundation.git' => 'sf_http_foundation/'),
	'autoload_config' => array('sf_http_foundation/' => 'Symfony\Component\HttpFoundation'),
	'example' => function() {
		$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
		echo $request->getPathInfo();
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
