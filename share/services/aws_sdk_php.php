#!/usr/bin/php
<?php

$config = array(
	'require_services' => array('guzzle3'),
	'git_urls' => array('https://github.com/yfix/aws-sdk-php.git' => 'aws-sdk-php/'),
	'autoload_config' => array('aws-sdk-php/src/Aws/' => 'Aws'),
	'example' => function() {
		$s3 = \Aws\S3\S3Client::factory();
		var_dump($s3);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
