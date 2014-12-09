#!/usr/bin/php
<?php

require dirname(__DIR__).'/bash_colors.php';

$tests = array(
	'yui' => array('echo "%s" | yuicompressor --type css', '.test { color:red; }', '.test{color:red}'),
	'cssembed' => array('echo "%s" | cssembed', '.test { color:red; }', '.test{color:red}'),
);
foreach ($tests as $name => $info) {
	$cmd = $info[0];
	$input = $info[1];
	$expected = $info[2];
	$ts = microtime(true);
	$name = bash_color_info(' '.$name.' ');
	exec(sprintf($cmd, $input).' 2>/dev/null', $result);
	$result_color = '';
	$error = '';
	if ($result[0] === $expected) {
		$result_color = bash_color_success(' OK ');
	} else {
		$result_color = bash_color_error(' ERROR ');
		$error = ' | \''.$expected.'\' differs from \''.$result[0].'\''."\t";
	}
	echo $name."\t". $cmd. "\t". $error. round(microtime(true) - $ts, 3). "\t". $result_color. PHP_EOL;
}
