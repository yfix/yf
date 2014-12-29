#!/usr/bin/php
<?php

ob_start();
require_once __DIR__.'/sf_translation.php';
ob_end_clean();

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/symfony/Validator.git' => $libs_root. 'sf_validator/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'sf_validator/' => 'Symfony\Component\Validator',
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

// Test mode when direct call
if (realpath($argv[0]) === realpath(__FILE__)) {
	$validator = \Symfony\Component\Validator\Validation::createValidator();
	$violations = $validator->validateValue('Bernhard', new \Symfony\Component\Validator\Constraints\Length(array('min' => 10)));
	var_dump($violations);
}
