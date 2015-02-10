#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/tchwork/jsqueeze.git' => 'jsqueeze/');
$autoload_config = array('jsqueeze/src/' => 'Patchwork');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$js = ' function  hello_world ( i , v ) { return " " ; } ';
	var_dump($js);
	$jz = new \Patchwork\JSqueeze();
	$min = $jz->squeeze($js, true, true, false);
	var_dump($min);

}
