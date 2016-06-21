#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/Propel2.git' => 'propel2/'],
	'autoload_config' => ['propel2/src/Propel/' => 'Propel'],
	'example' => function() {
		$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
		$serviceContainer->setAdapterClass('bookstore', 'mysql');
		$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
		$manager->setConfiguration([
			'dsn'      => 'mysql:host=localhost;dbname=yf_for_unit_tests',
			'user'     => 'root',
			'password' => '123456',
		]);
		$serviceContainer->setConnectionManager('bookstore', $manager);
		var_dump($manager);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
