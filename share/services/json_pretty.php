#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/camspiers/json-pretty.git' => 'json_pretty/'),
	'autoload_config' => array('json_pretty/src/Camspiers/JsonPretty/' => 'Camspiers\JsonPretty'),
	'example' => function() {
		$jsonPretty = new \Camspiers\JsonPretty\JsonPretty;
		echo $jsonPretty->prettify(array('test' => 'test'));
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
