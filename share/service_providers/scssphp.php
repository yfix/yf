#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/leafo/scssphp.git' => 'scssphp/');
$autoload_config = array('scssphp/src/' => 'Leafo\ScssPhp');
require __DIR__.'/_config.php';

require_once $libs_root.'scssphp/scss.inc.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$scss = new scssc();
	echo $scss->compile('
		$color: #abc;
		div { color: lighten($color, 20%); }
	');
}
