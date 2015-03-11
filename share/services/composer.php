#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('json_schema', 'jsonlint', 'sf_console', 'sf_finder', 'sf_process'),
	'git_urls' => array('https://github.com/composer/composer.git' => 'composer/'),
	'autoload_config' => array('composer/src/Composer/' => 'Composer'),
	'example' => function() {
		$input = new Symfony\Component\Console\Input\ArrayInput(array('command' => 'show', 'package' => 'leafo/scssphp'));
		$application = new Composer\Console\Application();
		$application->setAutoExit(false);
		$application->run($input);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
