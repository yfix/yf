#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/promise.git' => 'promise/'),
	'autoload_config' => array('promise/src/' => 'React\Promise'),
	'require_once' => array('promise/src/functions.php'),
	'example' => function() {
		$deferred = new \React\Promise\Deferred();
		$promise = $deferred->promise();
		var_dump($promise);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
