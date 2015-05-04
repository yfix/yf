#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/CssMin.git' => 'cssmin/'),
	'require_once' => array('cssmin/src/CssMin.php'),
	'example' => function() {
		$css = '.class { display: none ; } ';
		var_dump($css);
		$result = CssMin::minify($css);
		var_dump($result);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
