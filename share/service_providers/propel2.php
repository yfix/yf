#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/Propel2.git' => 'propel2/');
$autoload_config = array('propel2/src/Propel/' => 'Propel');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
	$serviceContainer->setAdapterClass('bookstore', 'mysql');
	$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
	$manager->setConfiguration(array (
		'dsn'      => 'mysql:host=localhost;dbname=yf_for_unit_tests',
		'user'     => 'root',
		'password' => '123456',
	));
	$serviceContainer->setConnectionManager('bookstore', $manager);
	var_dump($manager);
}
