#!/usr/bin/php
<?php

$config = [
	'require_services' => ['psr_http_message', 'phphttp_message_factory', 'clue_stream_filter'],
	'git_urls' => ['https://github.com/php-http/message.git' => 'phphttp_message/'],
	'autoload_config' => ['phphttp_message/src/' => 'Http\Message'],
	'example' => function() {
		var_dump(class_exists('Http\Message\Authentication\Bearer'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
