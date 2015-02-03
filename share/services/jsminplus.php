#!/usr/bin/php
<?php

$requires = array('minify');
require __DIR__.'/_config.php';

require_once $libs_root.'minify/min/lib/JSMinPlus.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$js = ' function  hello_world ( i , v ) { return " " ; } ';
	var_dump($js);
	$min = \JSMinPlus::minify($js);
	var_dump($min);

}
