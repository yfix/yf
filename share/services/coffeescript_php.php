#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/coffeescript-php.git' => 'coffeescript_php/');
$autoload_config = array();
require __DIR__.'/_config.php';

require $libs_root. 'coffeescript_php/src/CoffeeScript/Init.php';
\CoffeeScript\Init::load();

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$coffee = 'alert "I knew it!" if elvis?';
	echo \CoffeeScript\Compiler::compile($coffee, array('header' => false));
}
