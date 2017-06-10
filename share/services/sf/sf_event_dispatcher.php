#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/symfony/EventDispatcher.git' => 'sf_event_dispatcher/'],
	'autoload_config' => ['sf_event_dispatcher/' => 'Symfony\Component\EventDispatcher'],
	'example' => function() {
		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
		var_dump($dispatcher);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
