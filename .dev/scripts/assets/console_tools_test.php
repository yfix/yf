#!/usr/bin/php
<?php

require dirname(__DIR__).'/bash_colors.php';

$tests = array(
	'yui' => array('echo "%s" | yuicompressor --type css', '.test { color:red; }', '.test{color:red}'),
);
foreach ($tests as $name => $info) {
	$cmd = $info[0];
	$input = $info[1];
	$expected = $info[2];
	$ts = microtime(true);
	echo bash_color_info(' '.$name.' ')."\t";
	exec(sprintf($cmd, $input), $result);
	if ($result[0] === $expected) {
		echo bash_color_success(' OK ');
	} else {
		echo bash_color_error(' ERROR ');
		echo "\t".$input.' !== '."\t".$result[0]."\t";
	}
	echo "\t". $cmd. "\t". round(microtime(true) - $ts, 3). PHP_EOL;
}