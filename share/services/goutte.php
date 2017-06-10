#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'guzzle',
		'sf_browserkit',
		'sf_css_selector',
		'sf_dom_crawler',
		'promise'
	],
	'git_urls' => ['https://github.com/yfix/goutte.git' => 'goutte/'],
	'autoload_config' => ['goutte/Goutte/' => 'Goutte'],
	'example' => function() {
		$client = new Goutte\Client();
		$crawler = $client->request('GET', 'http://google.com/');
		$crawler->filter('head > title')->each(function ($node) {
		    print $node->text()."\n";
		});
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
