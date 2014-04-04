#!/usr/bin/php
<?php

$self = __FILE__;
foreach(glob(dirname(dirname(__FILE__)).'/*/*get_latest*.php') as $path) {
	if ($path == $self) {
		continue;
	}
	echo PHP_EOL.$path.PHP_EOL.PHP_EOL;
	require $path;
}
