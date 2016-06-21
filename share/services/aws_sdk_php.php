#!/usr/bin/php
<?php

$config = [
	'require_services' => ['guzzle3'],
	'git_urls' => ['https://github.com/yfix/aws-sdk-php.git' => 'aws-sdk-php/'],
	'autoload_config' => ['aws-sdk-php/src/Aws/' => 'Aws'],
	'example' => function() {
		$s3 = \Aws\S3\S3Client::factory();
		var_dump($s3);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
