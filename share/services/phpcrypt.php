#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/phpcrypt.git' => 'phpcrypt/');
$autoload_config = array('phpcrypt/' => 'PHP_Crypt');
require __DIR__.'/_config.php';

require_once $libs_root.'phpcrypt/phpCrypt.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$obj = new PHP_Crypt\PHP_Crypt('1234567890123456');
	var_dump($obj);

}
