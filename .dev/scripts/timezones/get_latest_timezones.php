#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_utils.php';

function get_latest_timezones() {

	$url = 'https://en.wikipedia.org/wiki/List_of_time_zone_abbreviations';
	$f2 = dirname(__FILE__).'/'.basename($url).'.table.html';
	if (!file_exists($f2)) {
		$html1 = file_get_contents($url);
		$regex1 = '~<table[^>]*wikitable[^>]*>(.*?)</table>~ims';
		preg_match($regex1, $html1, $m1);
		file_put_contents($f2, $m1[1]);
	}
	$html2 = file_get_contents($f2);
	#############
	$tmp_tbl = html_table_to_array($html2);
	#############
	$data = array();
	foreach ($tmp_tbl as $v) {
		$id = $v[0];
		if (!$id) {
			continue;
		}
		$data[$id] = array(
			'code'	=> $id,
			'name'	=> $v[1],
			'offset'=> $v[2],
			'active'=> 0,
		);
	}
	foreach (array('UTC','GMT','CET','EET','MSK') as $c) {
		$data[$c]['active'] = 1;
	}
	$f4 = dirname(__FILE__).'/timezones.php';
	file_put_contents($f4, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
	print_r($data);

}

get_latest_timezones();
