#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/kcfinder.git' => 'kcfinder/');
$autoload_config = array();
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	echo (int)file_exists($libs_root.'kcfinder/js_localize.php');

}
