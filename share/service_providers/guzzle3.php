#!/usr/bin/php
<?php

$requires = array('sf_event_dispatcher');
$git_urls = array('https://github.com/yfix/guzzle.git~v3.7.4' => 'guzzle3/');
$autoload_config = array('guzzle3/src/Guzzle/' => 'Guzzle');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	Guzzle\Http\StaticClient::mount();
	$response = Guzzle::get('http://google.com');
	echo strlen($response);
}
