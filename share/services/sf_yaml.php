#!/usr/bin/php
<?php

$config = [
	'git_urls' => ['https://github.com/yfix/Yaml.git' => 'sf_yaml/'],
	'autoload_config' => ['sf_yaml/' => 'Symfony\Component\Yaml'],
	'manual' => function() {
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
	},
	'example' => function() {
		$yaml_str = trim('
receipt:     Oz-Ware Purchase Invoice
date:        2012-08-06
customer:
    given:   Dorothy
    family:  Gale
		');
		$php_array = [
			'receipt' => 'Oz-Ware Purchase Invoice',
			'date' => 1344200400,
			'customer' => [
				'given' => 'Dorothy',
				'family' => 'Gale',
			],
		];

		var_export(yaml_parse($yaml_str));
		var_dump(yaml_dump($php_array));
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
