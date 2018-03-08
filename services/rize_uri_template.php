#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/rize/UriTemplate.git' => 'rize_uri_template/'],
	'autoload_config' => ['rize_uri_template/src/Rize/' => 'Rize'],
	'example' => function() {
		$uri = new Rize\UriTemplate('https://api.twitter.com/{version}', ['version' => 1.1]);
		echo $uri->expand('/statuses/show/{id}.json', ['id' => '210462857140252672']). PHP_EOL;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
