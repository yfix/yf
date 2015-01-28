#!/usr/bin/php
<?php

$requires = array();
#$git_urls = array('https://github.com/leafo/lessphp.git' => 'lessphp/');
#$autoload_config = array();
$composer_names = array('leafo/lessphp');
require __DIR__.'/_config.php';

#require $libs_root. 'lessphp/lessc.inc.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$less = new \lessc;
	echo $less->compile('.block { padding: 3 + 4px }');
}
