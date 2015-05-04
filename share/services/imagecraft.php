#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/coldume/imagecraft.git' => 'imagecraft/'),
	'autoload_config' => array('imagecraft/src/' => 'Imagecraft'),
	'example' => function() {
		$options = array('engine' => 'php_gd');
		$builder = new Imagecraft\ImageBuilder($options);
		$context = $builder->about();
		$res = $context->isEngineSupported();
		var_dump($res);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
