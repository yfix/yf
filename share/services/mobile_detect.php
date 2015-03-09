#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/serbanghita/Mobile-Detect.git' => 'mobile_detect/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'mobile_detect/Mobile_Detect.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$ua = 'mozilla/5.0 (x11; linux x86_64) applewebkit/537.36 (khtml, like gecko) chrome/40.0.2214.115 safari/537.36';
	$detect = new Mobile_Detect(array(), $ua);
	var_dump($ua);
	echo 'Is mobile: '.(int)$detect->isMobile(). PHP_EOL;

	$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1_2 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B440 Safari/600.1.4';
	$detect = new Mobile_Detect(array(), $ua);
	var_dump($ua);
	echo 'Is mobile: '.(int)$detect->isMobile(). PHP_EOL;

}
