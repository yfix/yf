#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/Seldaek/jsonlint.git' => 'jsonlint/');
$autoload_config = array('jsonlint/src/Seld/JsonLint/' => 'Seld\JsonLint');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$parser = new Seld\JsonLint\JsonParser();
	$json = '{"Hello":"World"}';
	$out = $parser->parse($json);
	var_dump($out);
}
