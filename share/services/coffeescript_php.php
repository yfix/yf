#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/coffeescript-php.git' => 'coffeescript_php/'),
	'require_once' => array('coffeescript_php/src/CoffeeScript/Init.php'),
	'manual' => function() {
		\CoffeeScript\Init::load();
	},
	'example' => function() {
		$coffee = 'alert "I knew it!" if elvis?';
		echo \CoffeeScript\Compiler::compile($coffee, array('header' => false));
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
