#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/tchwork/jsqueeze.git' => 'jsqueeze/'),
	'autoload_config' => array('jsqueeze/src/' => 'Patchwork'),
	'example' => function() {
		$js = ' function  hello_world ( i , v ) { return " " ; } ';
		var_dump($js);
		$jz = new \Patchwork\JSqueeze();
		$min = $jz->squeeze($js, true, true, false);
		var_dump($min);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
