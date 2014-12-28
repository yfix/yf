<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/sf_finder/';
	$prefix = 'Symfony\Component\Finder';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path.PHP_EOL;
		include $path;
	}
});

$finder = new \Symfony\Component\Finder\Finder();
$iterator = $finder
	->files()
	->name('*.php')
	->depth(0)
	->size('>= 1K')
	->in(__DIR__);

echo 'list of files with mask *.php inside current dir and in current subdir and size >= 1K'.PHP_EOL;
foreach ($iterator as $file) {
    print $file->getRealpath(). PHP_EOL;
}
