#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/EventDispatcher.git' => 'sf_event_dispatcher/');
$autoload_config = array('sf_event_dispatcher/' => 'Symfony\Component\EventDispatcher');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
	var_dump($dispatcher);
}
