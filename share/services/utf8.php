#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/tchwork/utf8.git' => 'utf8/');
$autoload_config = array('utf8/class/Patchwork/' => 'Patchwork');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$res = \Patchwork\Utf8\Bootup::initAll(); // Enables the portablity layer and configures PHP for UTF-8
	var_dump($res);

}
