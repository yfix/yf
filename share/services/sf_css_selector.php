#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/CssSelector.git' => 'sf_css_selector/');
$autoload_config = array('sf_css_selector/' => 'Symfony\Component\CssSelector');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	print \Symfony\Component\CssSelector\CssSelector::toXPath('div.item > h4 > a');
	print PHP_EOL;
}
