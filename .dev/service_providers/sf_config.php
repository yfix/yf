#!/usr/bin/php
<?php

$requires = array('sf_filesystem');
$git_urls = array('https://github.com/symfony/Config.git' => 'sf_config/');
$autoload_config = array('sf_config/' => 'Symfony\Component\Config');
require __DIR__.'/_config.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$treeBuilder = new \Symfony\Component\Config\Definition\Builder\TreeBuilder();
	$rootNode = $treeBuilder->root('database');
	$rootNode
		->children()
			->enumNode('gender')
				->values(array('male', 'female'))
			->end()
		->end();
	var_dump($treeBuilder);
}
