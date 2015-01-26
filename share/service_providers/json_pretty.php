#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/camspiers/json-pretty.git' => 'json_pretty/');
$autoload_config = array('json_pretty/src/Camspiers/JsonPretty/' => 'Camspiers\JsonPretty');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$jsonPretty = new \Camspiers\JsonPretty\JsonPretty;
	echo $jsonPretty->prettify(array('test' => 'test'));

}
