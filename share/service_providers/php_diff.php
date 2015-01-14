#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/php-diff.git' => 'php_diff/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'php_diff/lib/Diff.php';
require_once $libs_root.'php_diff/lib/Diff/Renderer/Html/SideBySide.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$str1 = explode(PHP_EOL, 'aaa'.PHP_EOL.'1');
	$str2 = explode(PHP_EOL, 'aaa'.PHP_EOL.'av');

	$diff = new Diff($str1, $str2, $options);
	echo $diff->Render(new Diff_Renderer_Html_SideBySide);
	echo PHP_EOL;

}
