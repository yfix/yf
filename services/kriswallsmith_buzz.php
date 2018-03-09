#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/kriswallsmith/Buzz.git' => 'kriswallsmith_buzz/'],
	'autoload_config' => ['kriswallsmith_buzz/lib/Buzz/' => 'Buzz'],
	'example' => function() {
		var_dump(class_exists('Buzz\Browser'));
		$browser = new Buzz\Browser();
		$response = $browser->get('http://www.google.com');
		echo $browser->getLastRequest(). PHP_EOL;
		echo 'response len:'.strlen($response);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
