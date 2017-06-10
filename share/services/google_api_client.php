#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'google_auth',
		'google_apiclient_services',
		'firebase_php_jwt',
		'monolog',
		'phpseclib',
		'guzzlehttp_guzzle',
		'guzzlehttp_psr7'
	],
	'git_urls' => ['https://github.com/google/google-api-php-client.git' => 'google_api_client/'],
# TODO: update, according to composer.json requirements changes
# https://github.com/google/google-api-php-client/blob/master/composer.json
#	'autoload_config' => ['google_api_client/src/Google/Service/' => 'Google_'],
	'pear' => ['google_api_client/src/' => 'Google_'],
	'example' => function() {
/*
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
*/
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
