#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/php5-utf8.git' => 'php5-utf8/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'php5-utf8/ReflectionTypeHint.php';
require_once $libs_root.'php5-utf8/UTF8.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$str = 'Строка для теста';
	echo UTF8::chunk_split($str, 5, '--');

}