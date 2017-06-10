#!/usr/bin/php
<?php

$config = [
	'require_services' => ['grpc','drslump_protobuf'],
	'git_urls' => ['https://github.com/googleapis/proto-client-php.git' => 'google_proto_client/'],
#	'autoload_config' => ['google_proto_client/src/' => 'no_cut_prefix:google'],
	'example' => function() {
#		var_dump(class_exists('google\pubsub\v1'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
