#!/usr/bin/php
<?php

$requires = array('sf_process', 'evenement', 'monolog', 'psr_log');
$git_urls = array('https://github.com/alchemy-fr/BinaryDriver.git' => 'binary_driver/');
$autoload_config = array('binary_driver/src/Alchemy/BinaryDriver/' => 'Alchemy\BinaryDriver');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	class test_ls_driver extends Alchemy\BinaryDriver\AbstractBinary {
		public function getName() {
			return 'ls driver';
		}
	}
	$parser = new test_ls_driver();
var_dump($parser);
#	$driver = Alchemy\BinaryDriver\Driver::load('ls');
#	// will return the output of `ls -a -l`
#	$parser->parse($driver->command(array('-a', '-l')));

}
