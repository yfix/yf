<?php

define('YF_PATH', dirname(dirname(__DIR__)).'/');

spl_autoload_register(function($class){
	$lib_root = YF_PATH.'libs/php-unified-archive/src/';
	$prefix = 'wapmorgan\UnifiedArchive';
	if (strpos($class, $prefix) === 0) {
		$path = $lib_root. str_replace("\\", '/', substr($class, strlen($prefix) + 1)).'.php';
#		echo $path.PHP_EOL;
		include $path;
	}
});

$out = \wapmorgan\UnifiedArchive\UnifiedArchive::archiveNodes('./form2', 'samples_archive.zip', $fake = true);
var_export($out);
