#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/symfony/Process.git' => 'sf_process/'),
	'autoload_config' => array('sf_process/' => 'Symfony\Component\Process'),
	'example' => function() {
		$process = new Symfony\Component\Process\Process('ls -lsa');
		$process->setTimeout(5);
		$process->run();
		print $process->getOutput();
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
