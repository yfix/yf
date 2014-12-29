#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/yfix/Yaml.git' => $libs_root. 'sf_yaml/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'sf_yaml/' => 'Symfony\Component\Yaml',
);
spl_autoload_register(function($class) use ($config) {
#	echo '=='.$class .PHP_EOL;
	foreach ($config as $lib_root => $prefix) {
		if (strpos($class, $prefix) !== 0) {
			continue;
		}
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
		if (!file_exists($path)) {
			continue;
		}
#		echo $path.PHP_EOL;
		include $path;
		return true;
	}
});

function yaml_parse($input) {
	return \Symfony\Component\Yaml\Yaml::parse($input);
}
function yaml_dump($yaml) {
	return \Symfony\Component\Yaml\Yaml::dump($yaml);
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