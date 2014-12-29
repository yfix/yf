#!/usr/bin/php
<?php

$requires = array();
$git_urls = array('https://github.com/yfix/Yaml.git' => 'sf_yaml/');
$autoload_config = array('sf_yaml/' => 'Symfony\Component\Yaml');
require __DIR__.'/_config.php';

if (!function_exists('yaml_parse')) {
	function yaml_parse($input) {
		return \Symfony\Component\Yaml\Yaml::parse($input);
	}
}
if (!function_exists('yaml_dump')) {
	function yaml_dump($yaml) {
		return \Symfony\Component\Yaml\Yaml::dump($yaml);
	}
}

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$yaml_str = '
receipt:     Oz-Ware Purchase Invoice
date:        2012-08-06
customer:
    given:   Dorothy
    family:  Gale
';

	$php_array = array(
		'receipt' => 'Oz-Ware Purchase Invoice',
		'date' => 1344200400,
		'customer' => array(
			'given' => 'Dorothy',
			'family' => 'Gale',
		),
	);

	var_export(yaml_parse($yaml_str));
	var_dump(yaml_dump($php_array));
}