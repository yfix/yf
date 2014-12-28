<?php

# git clone git@github.com:yfix/Propel2.git /home/www/yf/libs/propel2/

define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/propel2/src/Propel/';
	$prefix = 'Propel';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path. PHP_EOL;
		include $path;
	}
});

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
