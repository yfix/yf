#!/usr/bin/php
<?php

$config = [
	'require_services' => ['phphttp_httplug', 'phphttp_message_factory', 'phphttp_message', 'phphttp_discovery'],
	'git_urls' => ['https://github.com/php-http/curl-client.git' => 'phphttp_curl_client/'],
	'autoload_config' => ['phphttp_curl_client/src/' => 'Http\Client\Curl'],
	'example' => function() {
		var_dump(class_exists('Http\Client\Curl\Client'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
