#!/usr/bin/php
<?php

$config = array(
	'git_urls' => array('https://github.com/justinrainbow/json-schema.git' => 'json_schema/'),
	'autoload_config' => array('json_schema/src/JsonSchema/' => 'JsonSchema'),
	'example' => function() {
		$retriever = new JsonSchema\Uri\UriRetriever;
		var_dump($retriever);
	}
);
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
