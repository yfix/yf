<?php

# git clone git@github.com:yfix/DomCrawler.git /home/www/yf/libs/sf_dom_crawler/
define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/sf_dom_crawler/';
	$prefix = 'Symfony\Component\DomCrawler';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path.PHP_EOL;
		include $path;
	}
});

$crawler = new \Symfony\Component\DomCrawler\Crawler();
$crawler->addContent('<html><body><p>Hello World!</p></body></html>');
echo $crawler->filterXPath('descendant-or-self::body/p')->text() . PHP_EOL;
#print $crawler->filter('body > p')->text(); // require css selector
