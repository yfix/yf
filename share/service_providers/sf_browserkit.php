#!/usr/bin/php
<?php

$requires = array('sf_dom_crawler');
$git_urls = array('https://github.com/yfix/BrowserKit.git' => 'sf_browserkit/');
$autoload_config = array('sf_browserkit/' => 'Symfony\Component\BrowserKit');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$history = new \Symfony\Component\BrowserKit\History();
	var_dump($history);
}
