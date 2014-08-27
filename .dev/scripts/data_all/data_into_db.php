#!/usr/bin/php
<?php

require_once dirname(__DIR__).'/scripts_init.php';

$self = __FILE__;
foreach(glob(dirname(__DIR__).'/*/*into_db*.php') as $path) {
	if ($path == $self || false !== strpos($path, 'TODO')) {
		continue;
	}
	echo PHP_EOL.$path.PHP_EOL.PHP_EOL;
	require $path;
}
