#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/symfony/DependencyInjection.git' => 'sf_dependency_injection/'],
	'autoload_config' => ['sf_dependency_injection/' => 'Symfony\Component\DependencyInjection'],
	'example' => function() {
		$sc = new Symfony\Component\DependencyInjection\ContainerBuilder();
		$sc->register('foo', '%foo.class%')
			->addArgument(new Symfony\Component\DependencyInjection\Reference('bar'));
		$sc->setParameter('foo.class', 'Foo');
		var_dump($sc);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
