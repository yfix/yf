#!/usr/bin/php
<?php

$lang = 'uk';
$suffix = '_'.$lang;
$url = 'https://'.$lang.'.wikipedia.org/wiki/ISO_3166-1';
$result_file = __DIR__.'/countries_'.$lang.'.php';
$mtpl = array(
	'id'	=> 3,
	'code'	=> 3,
	'code3' => 2,
	'num'	=> 1,
	'name'	=> 0,
);

require __DIR__.'/get_latest_countries.php';
