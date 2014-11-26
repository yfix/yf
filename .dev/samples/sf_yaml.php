<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs';
require_once $libs_root.'/sf_class_loader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
	'Symfony\Component\Yaml'	=> $libs_root.'/sf_yaml',
));
$loader->register();

require_once $libs_root.'/sf_yaml/Yaml.php';

function yaml_parse($input) {
	return \Symfony\Component\Yaml\Yaml::parse($input);
}
function yaml_dump($yaml) {
	return \Symfony\Component\Yaml\Yaml::dump($yaml);
}

var_dump(yaml_parse('
    - apple
    - banana
    - carrot
'));

var_dump(yaml_dump(array(
	'apple',
	'banana',
	'carrot',
)));
