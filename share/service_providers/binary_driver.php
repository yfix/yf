#!/usr/bin/php
<?php

$requires = array('sf_process', 'monolog', 'psr_log', 'evenement', 'temporary_fs');
$git_urls = array('https://github.com/alchemy-fr/BinaryDriver.git' => 'binary_driver/');
$autoload_config = array('binary_driver/src/Alchemy/BinaryDriver/' => 'Alchemy\BinaryDriver');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$factory = new Alchemy\BinaryDriver\ProcessBuilderFactory('/usr/bin/php');
	// return a Symfony\Component\Process\Process
	$process = $factory->create('-v');
	// echoes '/usr/bin/php' '-v'
	echo $process->getCommandLine();

	$process = $factory->create(array('-r', 'echo "Hello !";'));
	// echoes '/usr/bin/php' '-r' 'echo "Hello !";'
	echo $process->getCommandLine();
	echo PHP_EOL;

}
