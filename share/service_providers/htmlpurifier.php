#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/htmlpurifier.git' => 'htmlpurifier/');
$autoload_config = array();
require __DIR__.'/_config.php';
require_once $libs_root. 'htmlpurifier/library/HTMLPurifier.auto.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$purifier = new HTMLPurifier();
	$dirty_html = '<script type="text/javascript">alert(\'Unsafe\');</script><p class=MsoNormal>Hoopla! This is some';
	$clean_html = $purifier->purify($dirty_html);
	echo $clean_html. PHP_EOL;
}
