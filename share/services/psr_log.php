#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/php-fig/log.git' => 'psr_log/');
$autoload_config = array('psr_log/Psr/Log/' => 'Psr\Log');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$logger = new Psr\Log\NullLogger();
	$logger->info('Doing work');
	var_dump($logger);
}
