#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Debug.git' => 'sf_debug/');
$autoload_config = array('sf_debug/' => 'Symfony\Component\Debug');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	echo (int)class_exists('\Symfony\Component\Debug\ErrorHandler');
}
