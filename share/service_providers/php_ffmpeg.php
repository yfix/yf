#!/usr/bin/php
<?php

$requires = array('binary_driver', 'doctrine_cache', 'evenement', 'temporary_fs');
$git_urls = array('https://github.com/yfix/PHP-FFMpeg' => 'php-ffmpeg/');
$autoload_config = array('php-ffmpeg/src/FFMpeg/' => 'FFMpeg');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$ffmpeg = \FFMpeg\FFMpeg::create();
	var_dump($ffmpeg);

}