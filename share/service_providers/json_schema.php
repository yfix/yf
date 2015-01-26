#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/justinrainbow/json-schema.git' => 'json_schema/');
$autoload_config = array('json_schema/src/JsonSchema/' => 'JsonSchema');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$retriever = new JsonSchema\Uri\UriRetriever;
	var_dump($retriever);
}
