#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/mrclay/minify.git' => 'minify/');
#$autoload_config = array('minify/' => '');
require __DIR__.'/_config.php';

require $libs_root. 'minify/min/lib/Minify.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

#	$js = ' function  hello_world ( i , v ) { return " " ; } ';
#	var_dump($js);
#	var_dump($min);
	var_dump(class_exists('Minify'));

}
