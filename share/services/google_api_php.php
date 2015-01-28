#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/google/google-api-php-client.git' => 'google_api_php/');
$autoload_config = array();
require __DIR__.'/_config.php';

require_once $libs_root.'google_api_php/autoload.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {

	$client = new Google_Client();
	$client->setApplicationName('Client_Library_Examples');
	$client->setDeveloperKey('YOUR_APP_KEY');

	$service = new Google_Service_Books($client);
	$optParams = array('filter' => 'free-ebooks');
#	$results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);
#	foreach ($results as $item) {
#		echo $item['volumeInfo']['title']. PHP_EOL;
#	}
	var_dump($client);

}
