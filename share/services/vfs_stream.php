#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/vfsStream.git' => 'vfs_stream/'),
	'autoload_config' => array('vfs_stream/src/main/php/' => 'no_cut_prefix:org\bovigo\vfs'),
	'example' => function() {
		$vdir = org\bovigo\vfs\vfsStream::setup('example_dir');
		var_dump($vdir);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
