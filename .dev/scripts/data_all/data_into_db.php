#!/usr/bin/php
<?php

$force = trim($argv[2]);
$project_path = trim($argv[1]);
if (!$project_path) {
	exit('Error: missing project_path. Example: '.basename(__FILE__).' /home/www/test2/'.PHP_EOL);
}
$project_path = rtrim($project_path, '/').'/';
foreach (array('', '*/', '*/*/', '*/*/*/') as $g) {
	$paths = glob($project_path. $g. 'db_setup.php');
	if (!$paths || !isset($paths[0])) {
		continue;
	}
	$fp = $paths[0];
	if ($fp && file_exists($fp)) {
		require $fp;
		break;
	}
}
if (!defined('DB_NAME')) {
	exit('Error: cannot init database connection.');
}

###########
if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(dirname(__FILE__)))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = true);
}
###########

$self = __FILE__;
foreach(glob(dirname(dirname(__FILE__)).'/*/*into_db*.php') as $path) {
	if ($path == $self || false !== strpos($path, 'TODO')) {
		continue;
	}
	echo PHP_EOL.$path.PHP_EOL.PHP_EOL;
	require $path;
}
