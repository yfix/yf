#!/usr/bin/php
<?php

if (!function_exists('html_table_to_array')) {
function html_table_to_array($html) {
	if (!preg_match_all('~<tr[^>]*>(.*?)</tr>~ims', $html, $m)) {
		return array();
	}
	$tmp_tbl = array();
	foreach ($m[1] as $v) {
		if (!preg_match_all('~<td[^>]*>(.*?)</td>~ims', $v, $m2)) {
			continue;
		}
		$val = $m2[1];
		// Get contents of within the tags, cannot be done with strip_tags
		$r = '~<[^>]+>(.*?)</[^>]+>~ims';
		$val = preg_replace($r, '$1', $val);
		$val = preg_replace($r, '$1', $val);
		$val = preg_replace($r, '$1', $val);
		foreach ($val as &$v1) {
			$v1 = trim(strip_tags($v1));
			$v1 = trim(preg_replace('~[\!&#]+.+~ims', '', $v1));
		}
		$tmp_tbl[] = $val;
	}
	return $tmp_tbl;
}
}

// TODO

$url = $url ?: 'https://en.wikipedia.org/wiki/ISO_3166-1';
$result_file = $result_file ?: dirname(__FILE__).'/countries.php';
$suffix = $suffix ?: '';

if (!function_exists('data_get_latest_regions')) {
function data_get_latest_regions() {
	global $url, $result_file, $suffix;
/*
	$f2 = dirname(__FILE__).'/'.basename($url).'.table'.$suffix.'.html';
	if (!file_exists($f2)) {
		$html1 = file_get_contents($url);
		$regex1 = '~<table[^>]*wikitable[^>]*>(.*?)</table>~ims';
		preg_match($regex1, $html1, $m1);
		file_put_contents($f2, $m1[1]);
	}
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

data_get_latest_regions();