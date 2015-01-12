#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Filesystem.git' => 'sf_filesystem/');
$autoload_config = array('sf_filesystem/' => 'Symfony\Component\Filesystem');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$filesystem = new Symfony\Component\Filesystem\Filesystem();
	$res = $filesystem->exists(__FILE__);
	var_dump($res);
}
