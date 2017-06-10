#!/usr/bin/php
<?php

$config = [
	'require_services' => [
		'psr_http_message',
		'phphttp_message_factory',
		'phphttp_discovery'
	],
	'git_urls' => ['https://github.com/php-http/multipart-stream-builder.git' => 'phphttp_multipart_stream_builder/'],
	'autoload_config' => ['phphttp_multipart_stream_builder/src/' => 'Http\Message\MultipartStream'],
	'example' => function() {
		var_dump(class_exists('Http\Message\MultipartStream\MultipartStreamBuilder'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
