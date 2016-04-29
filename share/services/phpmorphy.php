#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/phpmorphy.git' => 'phpmorphy/'),
	'require_once' => array('phpmorphy/src/common.php'),
	'example' => function($loader) {
		$dict_bundle = new phpMorphy_FilesBundle($dir, 'rus');
		var_dump($dict_bundle);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
