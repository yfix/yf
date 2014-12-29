#!/usr/bin/php
<?php

$requires = array('sf_css_selector');
$git_urls = array('https://github.com/yfix/DomCrawler.git' => 'sf_dom_crawler/');
$autoload_config = array('sf_dom_crawler/' => 'Symfony\Component\DomCrawler');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!$_SERVER['REQUEST_METHOD'] && realpath($argv[0]) === realpath(__FILE__)) {
	$crawler = new \Symfony\Component\DomCrawler\Crawler();
	$crawler->addContent('<html><body><p>Hello World!</p></body></html>');
	echo $crawler->filterXPath('descendant-or-self::body/p')->text();
	echo PHP_EOL;
	echo $crawler->filter('body > p')->text(); // require css selector
	echo PHP_EOL;
}
