#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('sf_process', 'monolog', 'psr_log', 'evenement', 'temporary_fs'),
	'git_urls' => array('https://github.com/alchemy-fr/BinaryDriver.git' => 'binary_driver/'),
	'autoload_config' => array('binary_driver/src/Alchemy/BinaryDriver/' => 'Alchemy\BinaryDriver'),
	'example' => function($obj) {
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
);
if ($return_config) { return $config; } require __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
