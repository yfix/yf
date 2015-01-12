#!/usr/bin/php
<?php

$requires = array('composer', 'jsonlint');
$git_urls = array('https://github.com/dflydev/dflydev-embedded-composer.git' => 'embedded-composer/');
$autoload_config = array('embedded-composer/src/Dflydev/EmbeddedComposer/' => 'Dflydev\EmbeddedComposer');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$classLoader = new Composer\Autoload\ClassLoader();
	$embedded_composer_builder = new Dflydev\EmbeddedComposer\Core\EmbeddedComposerBuilder($classLoader, './');
	$embedded_composer = $embedded_composer_builder
		->setComposerFilename('myapp.json')
		->setVendorDirectory('.myapp')
		->build();
	$embedded_composer->processAdditionalAutoloads();
	$out = $embedded_composer->findPackage('dflydev/embedded-composer');
	var_dump($out);
}
