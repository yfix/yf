#!/usr/bin/php
<?php

$config = [
	'require_services' => ['guzzlehttp_guzzle', 'guzzlehttp_psr7', 'guzzlehttp_promises', 'jmespath'],
	'git_urls' => ['https://github.com/aws/aws-sdk-php.git' => 'aws_sdk_v3/'],
	'require_once' => ['aws_sdk_v3/src/functions.php'],
	'autoload_config' => ['aws_sdk_v3/src/' => 'Aws'],
	'example' => function() {
		var_dump(class_exists('Aws\S3\S3Client'));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
