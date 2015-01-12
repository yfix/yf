#!/usr/bin/php
<?php

$requires = array('json_schema', 'jsonlint', 'sf_console', 'sf_finder', 'sf_process');
$git_urls = array('https://github.com/composer/composer.git' => 'composer/');
$autoload_config = array('composer/src/Composer/' => 'Composer');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$input = new Symfony\Component\Console\Input\ArrayInput(array('command' => 'show', 'package' => 'leafo/scssphp'));
	$application = new Composer\Console\Application();
	$application->setAutoExit(false);
	$application->run($input);
}
