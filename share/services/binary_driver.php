#!/usr/bin/php
<?php

$config = [
	'require_services' => ['sf_process', 'monolog', 'psr_log', 'evenement', 'temporary_fs'],
	'git_urls' => ['https://github.com/alchemy-fr/BinaryDriver.git' => 'binary_driver/'],
	'autoload_config' => ['binary_driver/src/Alchemy/BinaryDriver/' => 'Alchemy\BinaryDriver'],
	'example' => function() {
		$factory = new Alchemy\BinaryDriver\ProcessBuilderFactory('/usr/bin/php');
		// return a Symfony\Component\Process\Process
		$process = $factory->create('-v');
		// echoes '/usr/bin/php' '-v'
		echo $process->getCommandLine();

		$process = $factory->create(['-r', 'echo "Hello !";']);
		// echoes '/usr/bin/php' '-r' 'echo "Hello !";'
		echo $process->getCommandLine();
		echo PHP_EOL;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
