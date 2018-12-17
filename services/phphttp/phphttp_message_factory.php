#!/usr/bin/php
<?php

$config = [
	'require_services' => ['psr_http_message'],
	'git_urls' => ['https://github.com/php-http/message-factory.git' => 'phphttp_message_factory/'],
	'autoload_config' => ['phphttp_message_factory/src/' => 'Http\Message'],
	'example' => function() {
		var_dump(interface_exists('Http\Message\MessageFactory'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
