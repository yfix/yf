#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/ClassLoader.git' => 'sf_class_loader/');
$autoload_config = array('sf_class_loader/' => 'Symfony\Component\ClassLoader');
require __DIR__.'/_config.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
	var_dump($loader);
}
