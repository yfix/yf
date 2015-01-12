#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/HttpFoundation.git' => 'sf_http_foundation/');
$autoload_config = array('sf_http_foundation/' => 'Symfony\Component\HttpFoundation');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
	echo $request->getPathInfo();
}
