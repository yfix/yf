#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Process.git' => 'sf_process/');
$autoload_config = array('sf_process/' => 'Symfony\Component\Process');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$process = new Symfony\Component\Process\Process('ls -lsa');
	$process->setTimeout(5);
	$process->run();
	print $process->getOutput();
}
