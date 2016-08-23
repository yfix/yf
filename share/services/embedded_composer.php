#!/usr/bin/php
<?php

$config = [
	'require_services' => ['composer', 'jsonlint'],
	'git_urls' => ['https://github.com/dflydev/dflydev-embedded-composer.git' => 'embedded-composer/'],
	'autoload_config' => ['embedded-composer/src/Dflydev/EmbeddedComposer/' => 'Dflydev\EmbeddedComposer'],
	'example' => function() {
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
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
