<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs';
/*
require_once $libs_root.'/sf_class_loader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
	'Symfony\Component\Yaml'	=> $libs_root.'/sf_yaml',
));
$loader->register();
*/
$files = array(
	'Exception/ExceptionInterface.php',
	'Exception/RuntimeException.php',
	'Exception/ParseException.php',
	'Inline.php',
	'Parser.php',
	'Dumper.php',
	'Escaper.php',
	'Unescaper.php',
	'Yaml.php',
);
foreach ($files as $file) {
	require_once $libs_root. '/sf_yaml/'. $file;
}

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
