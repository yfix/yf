<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/sf_yaml/';
	$prefix = 'Symfony\Component\Yaml';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path.PHP_EOL;
		include $path;
	}
});

function yaml_parse($input) {
	return \Symfony\Component\Yaml\Yaml::parse($input);
}
function yaml_dump($yaml) {
	return \Symfony\Component\Yaml\Yaml::dump($yaml);
}

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
