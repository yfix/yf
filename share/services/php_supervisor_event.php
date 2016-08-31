#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/php-supervisor-event.git' => 'php-supervisor-event/'],
	'autoload_config' => ['php-supervisor-event/src/Mtdowling/Supervisor/' => 'Mtdowling\Supervisor'],
	'example' => function() {
		print (int)class_exists('Mtdowling\Supervisor\EventListener');
		print (int)class_exists('Mtdowling\Supervisor\EventNotification');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
