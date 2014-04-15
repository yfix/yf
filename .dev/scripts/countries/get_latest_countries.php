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
		foreach ($val as &$v1) {
			if (preg_match('~<[^>]+>([^<]+?)</[^>]+>~ims', $v1, $mm)) {
				$v1 = $mm[1];
			}
#			$v1 = trim(strip_tags($v1));
			$v1 = trim(preg_replace('~&[#]?[0-9a-z]+;~ims', '', $v1));
			$v1 = trim(preg_replace('~\!.+~ims', '', $v1));
		}
		$tmp_tbl[] = $val;
	}
	return $tmp_tbl;
}
}

// TODO: try json api from wikipedia
#$u = 'http://en.wikipedia.org/w/api.php?format=json&action=query&titles=ISO_3166-1&prop=langlinks';
#print_r(json_decode(file_get_contents($u), 1));

// TODO: list of continents and country mapping
#$url = 'http://unstats.un.org/unsd/methods/m49/m49regin.htm';

$url = $url ?: 'https://en.wikipedia.org/wiki/ISO_3166-1';
$result_file = $result_file ?: dirname(__FILE__).'/countries.php';
$suffix = $suffix ?: '';
$mtpl = $mtpl ?: array(
	'id'	=> 1,
	'code'	=> 1,
	'code3' => 2,
	'num'	=> 3,
	'name'	=> 0,
);

if (!function_exists('data_get_latest_countries')) {
function data_get_latest_countries() {
	global $url, $result_file, $suffix, $mtpl;
print_r($mtpl);

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
		$id = $v[$mtpl['id']];
		if (!$id) {
			continue;
		}
		$data[$id] = array(
			'code'	=> $id,
			'code3' => $v[$mtpl['code3']],
			'num'	=> $v[$mtpl['num']],
			'name'	=> $v[$mtpl['name']],
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
}
}

data_get_latest_countries();