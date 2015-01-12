#!/usr/bin/php
<?php

$requires = array('json_schema', 'jsonlint', 'sf_console', 'sf_finder', 'sf_process');
$git_urls = array('https://github.com/composer/composer.git' => 'composer/');
$autoload_config = array('composer/src/Composer/' => 'Composer');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$application = new Composer\Console\Application();
	$application->run();
}
