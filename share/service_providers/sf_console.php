#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/Console.git' => 'sf_console/');
$autoload_config = array('sf_console/' => 'Symfony\Component\Console');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$console = new Symfony\Component\Console\Application();
	$console->run();
}
