#!/usr/bin/php
<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');
$libs_root = YF_PATH.'libs/';

$git_urls = array(
	'https://github.com/symfony/Translation.git' => $libs_root. 'sf_translation/',
);
foreach ($git_urls as $git_url => $lib_dir) {
	if (!file_exists($lib_dir.'.git')) {
		passthru('git clone --depth 1 '.$git_url.' '.$lib_dir);
	}
}
$config = array(
	$libs_root. 'sf_translation/' => 'Symfony\Component\Translation',
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
	$translator = new \Symfony\Component\Translation\Translator('fr_FR', new Symfony\Component\Translation\MessageSelector());
	$translator->setFallbackLocales(array('fr'));
	$translator->addLoader('array', new Symfony\Component\Translation\Loader\ArrayLoader());
	$translator->addResource('array', array(
		'Hello World!' => 'Bonjour',
	), 'fr');
	echo $translator->trans('Hello World!')."\n";
}
