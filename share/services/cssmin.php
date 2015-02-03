#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/CssMin.git' => 'cssmin/');
require __DIR__.'/_config.php';

require_once $libs_root.'cssmin/src/CssMin.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$css = '.class { display: none ; } ';
	var_dump($css);
	$result = CssMin::minify($css);
	var_dump($result);

}
