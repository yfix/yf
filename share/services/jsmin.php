#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/linkorb/jsmin-php.git' => 'jsmin-php/');
require __DIR__.'/_config.php';

require_once $libs_root.'jsmin-php/src/jsmin-1.1.1.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$js = ' function  hello_world ( i , v ) { return " " ; } ';
	var_dump($js);
	$min = \JSMin::minify($js);
	var_dump($min);

}
