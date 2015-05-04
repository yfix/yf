#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/symfony/security-core.git' => 'sf_security_core/'),
	'autoload_config' => array('sf_security_core/' => 'Symfony\Component\Security\Core'),
	'example' => function() {
#		$history = new \Symfony\Component\BrowserKit\History();
#		var_dump($history);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
