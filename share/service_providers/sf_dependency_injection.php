#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/DependencyInjection.git' => 'sf_dependency_injection/');
$autoload_config = array('sf_dependency_injection/' => 'Symfony\Component\DependencyInjection');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$sc = new Symfony\Component\DependencyInjection\ContainerBuilder();
	$sc->register('foo', '%foo.class%')
		->addArgument(new Symfony\Component\DependencyInjection\Reference('bar'));
	$sc->setParameter('foo.class', 'Foo');

	var_dump($sc);
}
