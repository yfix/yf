#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/avalanche123/Imagine.git' => 'imagine/');
$autoload_config = array('imagine/lib/Imagine/' => 'Imagine');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$imagine = new \Imagine\Gd\Imagine();
	var_dump($imagine);

}
