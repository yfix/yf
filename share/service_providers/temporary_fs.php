#!/usr/bin/php
<?php

$requires = array('sf_filesystem');
$git_urls = array('https://github.com/romainneutron/Temporary-Filesystem.git' => 'temporary_fs/');
$autoload_config = array('temporary_fs/src/Neutron/TemporaryFilesystem/' => 'Neutron\TemporaryFilesystem');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$fs = \Neutron\TemporaryFilesystem\TemporaryFilesystem::create();
	$fs->createTemporaryFile('thumb-', '.dcm', 'jpg');
	var_dump($fs);

}
