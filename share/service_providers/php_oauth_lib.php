#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/Lusitanian/PHPoAuthLib.git' => 'php_oauth_lib/');
$autoload_config = array('php_oauth_lib/src/OAuth/' => 'OAuth');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
	var_dump($uriFactory);

}