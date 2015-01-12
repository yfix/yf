#!/usr/bin/php
<?php

$requires = array('guzzle', 'sf_browserkit', 'sf_css_selector', 'sf_dom_crawler', 'promise');
$git_urls = array('https://github.com/yfix/goutte.git' => 'goutte/');
$autoload_config = array('goutte/Goutte/' => 'Goutte');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$client = new Goutte\Client();
	$crawler = $client->request('GET', 'http://google.com/');
	$crawler->filter('head > title')->each(function ($node) {
	    print $node->text()."\n";
	});
}
