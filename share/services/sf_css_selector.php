#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/yfix/CssSelector.git' => 'sf_css_selector/'),
	'autoload_config' => array('sf_css_selector/' => 'Symfony\Component\CssSelector'),
	'example' => function() {
		print \Symfony\Component\CssSelector\CssSelector::toXPath('div.item > h4 > a');
		print PHP_EOL;
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
