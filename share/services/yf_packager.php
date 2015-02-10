#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/packager.git' => 'yf_packager/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'yf_packager/packager.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$pkg = new Packager();
	var_dump($pkg);

}
