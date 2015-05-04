#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/Seldaek/jsonlint.git' => 'jsonlint/'),
	'autoload_config' => array('jsonlint/src/Seld/JsonLint/' => 'Seld\JsonLint'),
	'example' => function() {
		$parser = new Seld\JsonLint\JsonParser();
		$json = '{"Hello":"World"}';
		$out = $parser->parse($json);
		var_dump($out);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
