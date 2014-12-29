#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/nrk/predis.git' => 'predis/');
$autoload_config = array('predis/src/' => 'Predis');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$client = new Predis\Client(array(
	    'scheme' => 'tcp',
	    'host'   => '127.0.0.1',
    	'port'   => 6379,
	));
	$client->set('foo', 'bar');
	$value = $client->get('foo');
}
