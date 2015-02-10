#!/usr/bin/php
<?php

$requires = array('sf_console', 'sf_finder', 'sf_filesystem', 'sf_process', 'php_semver', 'php_github_api', 'json_pretty');
$git_urls = array('https://github.com/Bee-Lab/bowerphp.git' => 'bowerphp/');
$autoload_config = array('bowerphp/src/Bowerphp/' => 'Bowerphp');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$input = new Symfony\Component\Console\Input\ArrayInput(array('command' => 'info', 'package' => 'jquery'));
	$application = new Bowerphp\Console\Application();
	$application->setAutoExit(false);
	$application->run($input);

}
