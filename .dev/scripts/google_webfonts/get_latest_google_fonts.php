#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_utils.php';

// TODO

$url = $url ?: 'https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyAMEY_I9-FBC4xH--Smf0-E38Mguk-1PVU';
$result_file = $result_file ?: dirname(__FILE__).'/google_webfonts.php';
$suffix = $suffix ?: '';

if (!function_exists('data_get_latest_google_fonts')) {
function data_get_latest_google_fonts() {
	global $url, $result_file, $suffix;

	$f2 = dirname(__FILE__).'/google_webfonts'.$suffix.'.json';
	if (!file_exists($f2)) {
		file_put_contents($f2, file_get_contents($url));
	}
/*
	$html2 = file_get_contents($f2);

	$tmp_tbl = html_table_to_array($html2);
	$data = array();
	foreach ($tmp_tbl as $v) {
		$id = $v[1];
		if (!$id) {
			continue;
		}
		$data[$id] = array(
			'code'	=> $id,
			'code3' => $v[2],
			'num'	=> $v[3],
			'name'	=> $v[0],
			'cont'	=> '',
			'active'=> 0,
		);
	}
	foreach (array('UA','RU','US','DE','FR','ES','GB') as $c) {
		$data[$c]['active'] = 1;
	}

	$f4 = $result_file;
	file_put_contents($f4, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
	print_r($data);
*/
}
}

data_get_latest_google_fonts();