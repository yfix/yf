#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/krichprollsch/phpCssEmbed.git' => 'cssembed/'),
	'autoload_config' => array('cssembed/src/CssEmbed/' => 'CssEmbed'),
	'example' => function() {
		$css = '.test { display: none; }';
		$pce = new \CssEmbed\CssEmbed();
		echo $pce->embedString( $css );
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
