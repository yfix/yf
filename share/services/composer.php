#!/usr/bin/php
<?php

$config = [
	'require_services' => ['json_schema', 'jsonlint', 'sf_console', 'sf_finder', 'sf_process'],
	'git_urls' => ['https://github.com/composer/composer.git' => 'composer/'],
	'autoload_config' => ['composer/src/Composer/' => 'Composer'],
	'example' => function() {
		$input = new Symfony\Component\Console\Input\ArrayInput(['command' => 'show', 'package' => 'leafo/scssphp']);
		$application = new Composer\Console\Application();
		$application->setAutoExit(false);
		$application->run($input);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
