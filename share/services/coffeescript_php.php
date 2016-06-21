#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/coffeescript-php.git' => 'coffeescript_php/'],
	'require_once' => ['coffeescript_php/src/CoffeeScript/Init.php'],
	'manual' => function() {
		\CoffeeScript\Init::load();
	},
	'example' => function() {
		$coffee = 'alert "I knew it!" if elvis?';
		echo \CoffeeScript\Compiler::compile($coffee, ['header' => false]);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
