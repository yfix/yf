#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/tchwork/utf8.git' => 'utf8/'),
	'autoload_config' => array('utf8/class/Patchwork/' => 'Patchwork'),
	'example' => function() {
		$res = \Patchwork\Utf8\Bootup::initAll(); // Enables the portablity layer and configures PHP for UTF-8
		var_dump($res);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
