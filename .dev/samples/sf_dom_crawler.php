#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/DomCrawler.git' => $libs_root. 'sf_dom_crawler/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'sf_dom_crawler/' => 'Symfony\Component\DomCrawler',
);
spl_autoload_register(function($class) use ($config) {
#	echo '=='.$class .PHP_EOL;
	foreach ($config as $lib_root => $prefix) {
		if (strpos($class, $prefix) !== 0) {
			continue;
		}
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
		if (!file_exists($path)) {
			continue;
		}
#		echo $path.PHP_EOL;
		include $path;
		return true;
	}
});

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$crawler = new \Symfony\Component\DomCrawler\Crawler();
	$crawler->addContent('<html><body><p>Hello World!</p></body></html>');
	echo $crawler->filterXPath('descendant-or-self::body/p')->text() . PHP_EOL;
//	print $crawler->filter('body > p')->text(); // require css selector
}