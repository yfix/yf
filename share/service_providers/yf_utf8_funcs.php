#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/yf_utf8_funcs.git' => 'yf_utf8_funcs/');
$autoload_config = array();
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$str = 'Строка для теста';
	include_once $libs_root.'yf_utf8_funcs/utf8_chunk_split.php';
	echo utf8_chunk_split($str, 5, '--');
}
