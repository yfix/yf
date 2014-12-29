#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/symfony/Serializer.git' => 'sf_serializer/');
$autoload_config = array('sf_serializer/' => 'Symfony\Component\Serializer');
require __DIR__.'/_config.php';

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$encoders = array(new Symfony\Component\Serializer\Encoder\XmlEncoder(), new Symfony\Component\Serializer\Encoder\JsonEncoder());
	$normalizers = array(new Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer());

	$serializer = new Symfony\Component\Serializer\Serializer($normalizers, $encoders);
	$person = array(
		'name' => 'John',
		'surname' => 'Doe',
		'age' => '100',
	);
	$jsonContent = $serializer->serialize($person, 'json');
	echo $jsonContent;
}
