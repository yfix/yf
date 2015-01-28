#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/coldume/imagecraft.git' => 'imagecraft/');
$autoload_config = array('imagecraft/src/' => 'Imagecraft');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$options = array('engine' => 'php_gd');
	$builder = new Imagecraft\ImageBuilder($options);
	$context = $builder->about();
	$res = $context->isEngineSupported();
	var_dump($res);
}
