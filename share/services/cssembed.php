#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/krichprollsch/phpCssEmbed.git' => 'cssembed/');
$autoload_config = array('cssembed/src/CssEmbed/' => 'CssEmbed');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$css = '.test { display: none; }';
#	var_dump($js);
#	var_dump($min);
	$pce = new \CssEmbed\CssEmbed();
	echo $pce->embedString( $css );

}
