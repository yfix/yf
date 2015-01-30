#!/usr/bin/php
<?php

$requires = array('guzzle3');
$git_urls = array('https://github.com/yfix/aws-sdk-php.git' => 'aws-sdk-php/');
$autoload_config = array('aws-sdk-php/src/Aws/' => 'Aws');
require __DIR__.'/_config.php';

// Test mode when direct call
if (!isset($_SERVER['REQUEST_METHOD']) && realpath($argv[0]) === realpath(__FILE__)) {
	$s3 = \Aws\S3\S3Client::factory();
	var_dump($s3);
}