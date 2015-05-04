#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/flysystem.git' => 'flysystem/'),
	'autoload_config' => array('flysystem/src/' => 'League\Flysystem'),
	'example' => function() {
		$filesystem = new League\Flysystem\Filesystem(new League\Flysystem\Adapter\Local(__DIR__));
		foreach ($filesystem->listContents() as $p) {
			echo $p['path']. PHP_EOL;
		}
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
