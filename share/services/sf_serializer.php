#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/symfony/Serializer.git' => 'sf_serializer/'),
	'autoload_config' => array('sf_serializer/' => 'Symfony\Component\Serializer'),
	'example' => function() {
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
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
