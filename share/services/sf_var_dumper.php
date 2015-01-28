#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/var-dumper.git' => 'sf_var_dumper/');
$autoload_config = array('sf_var_dumper/' => 'Symfony\Component\VarDumper');
require __DIR__.'/_config.php';

if (!function_exists('dump')) {
	function dump($var) {
		foreach (func_get_args() as $var) {
			\Symfony\Component\VarDumper\VarDumper::dump($var);
		}
	}
}

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	dump($_SERVER);
}