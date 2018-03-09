#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/paragonie/constant_time_encoding.git' => 'paragonie_constant_time_encoding/'],
	'autoload_config' => ['paragonie_constant_time_encoding/src/' => 'ParagonIE\ConstantTime'],
	'example' => function() {
		$out = \ParagonIE\ConstantTime\Encoding::base32Encode(random_bytes(32));
		var_dump($out);
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
