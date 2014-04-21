#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$self = __FILE__;
foreach(glob(dirname(dirname(__FILE__)).'/*/*into_db*.php') as $path) {
	if ($path == $self || false !== strpos($path, 'TODO')) {
		continue;
	}
	echo PHP_EOL.$path.PHP_EOL.PHP_EOL;
	require $path;
}
