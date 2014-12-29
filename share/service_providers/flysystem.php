#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/flysystem.git' => 'flysystem/');
$autoload_config = array('flysystem/src/' => 'League\Flysystem');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$filesystem = new League\Flysystem\Filesystem(new League\Flysystem\Adapter\Local(__DIR__));
	$paths = $filesystem->listPaths();
	foreach ($paths as $path) {
		echo $path. PHP_EOL;
	}
}
