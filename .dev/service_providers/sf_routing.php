#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Routing.git' => 'sf_routing/');
$autoload_config = array('sf_routing/' => 'Symfony\Component\Routing');
require __DIR__.'/_config.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$route = new Symfony\Component\Routing\Route('/hello', array('controller' => 'foo'));
	var_dump($route);
}
