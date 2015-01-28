#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/vfsStream.git' => 'vfs_stream/');
$autoload_config = array('vfs_stream/src/main/php/' => 'no_cut_prefix:org\bovigo\vfs');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$vdir = org\bovigo\vfs\vfsStream::setup('example_dir');
	var_dump($vdir);
}
