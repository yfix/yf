#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/briannesbitt/Carbon.git' => 'carbon/');
$autoload_config = array('carbon/src/Carbon/' => 'Carbon');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$diff = Carbon\Carbon::now()->subDays(5)->diffForHumans();
	echo $diff.PHP_EOL;

}
