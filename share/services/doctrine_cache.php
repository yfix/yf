#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/doctrine/cache.git' => 'doctrine_cache/');
$autoload_config = array('doctrine_cache/lib/Doctrine/Common/Cache/' => 'Doctrine\Common\Cache');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$cache = new \Doctrine\Common\Cache\ArrayCache();
	$id = $cache->fetch('some key');
	if (!$id) {
		$id = 'something';
		$cache->save('some key', $id);
	}
	echo $cache->fetch('some key'). PHP_EOL;

}
