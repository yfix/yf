#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/promise.git' => 'promise/');
$autoload_config = array('promise/src/' => 'React\Promise');
require __DIR__.'/_config.php';

require_once $libs_root. 'promise/src/functions.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$deferred = new \React\Promise\Deferred();
	$promise = $deferred->promise();
	var_dump($promise);
}
