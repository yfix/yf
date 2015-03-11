#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('binary_driver', 'doctrine_cache', 'evenement', 'temporary_fs'),
	'git_urls' => array('https://github.com/yfix/PHP-FFMpeg' => 'php-ffmpeg/'),
	'autoload_config' => array('php-ffmpeg/src/FFMpeg/' => 'FFMpeg'),
	'example' => function() {
		$ffmpeg = \FFMpeg\FFMpeg::create();
		var_dump($ffmpeg);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
