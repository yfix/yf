#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/google/google-api-php-client.git' => 'google_api_php/'],
	'require_once' => ['google_api_php/autoload.php'],
	'example' => function() {
		$client = new Google_Client();
		$client->setApplicationName('Client_Library_Examples');
		$client->setDeveloperKey('YOUR_APP_KEY');

		$service = new Google_Service_Books($client);
		$optParams = ['filter' => 'free-ebooks'];
#		$results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);
#		foreach ($results as $item) {
#			echo $item['volumeInfo']['title']. PHP_EOL;
#		}
		var_dump($client);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
