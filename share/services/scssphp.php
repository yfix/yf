#!/usr/bin/php
<?php

$config = [
	'composer_names' => ['leafo/scssphp'],
	'example' => function() {
		$scss = new scssc();
		echo $scss->compile('
			$color: #abc;
			div { color: lighten($color, 20%); }
		');
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
