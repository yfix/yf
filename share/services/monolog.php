#!/usr/bin/php
<?php

$requires = array('psr_log');
$git_urls = array('https://github.com/Seldaek/monolog.git' => 'monolog/');
$autoload_config = array('monolog/src/Monolog/' => 'Monolog');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$file = '/tmp/test_monolog.log';
	file_exists($file) && unlink($file);

	// create a log channel
	$log = new Monolog\Logger('name');
	$log->pushHandler(new Monolog\Handler\StreamHandler($file, Monolog\Logger::WARNING));

	// add records to the log
	$log->addWarning('Foo');
	$log->addError('Bar');

	echo file_get_contents($file);

}
