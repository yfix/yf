#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/php-fig/log.git' => 'psr_log/'),
	'autoload_config' => array('psr_log/Psr/Log/' => 'Psr\Log'),
	'example' => function() {
		$logger = new Psr\Log\NullLogger();
		$logger->info('Doing work');
		var_dump($logger);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
