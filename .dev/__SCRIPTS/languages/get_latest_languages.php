#!/usr/bin/php
<?php

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
		$val = preg_replace('~\[[^\]]+\]~ims', '', $val);
		foreach ($val as &$v1) {
			$v1 = trim(strip_tags($v1));
		}
		$tmp_tbl[] = $val;
	}
	return $tmp_tbl;
}

$url = 'https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes';
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
	$id = $v[4];
	if (!$id) {
		continue;
	}
	$data[$id] = array(
		'code'	=> $id,
		'name'	=> $v[2],
		'native'=> trim($v[3]),
		'code3' => $v[5],
		'country'=> '',
		'active'=> 0,
	);
}
foreach (array('en','ru','uk') as $c) {
	$data[$c]['active'] = 1;
}
$lang_to_country = array(
	'en' => 'us',
	'ru' => 'ru',
	'uk' => 'ua',
	'be' => 'by',
	'it' => 'it',
	'de' => 'de',
	'fr' => 'fr',
	'es' => 'es',
	'pt' => 'pt',
	'da' => 'dk',
	'nl' => 'nl',
	'no' => 'no',
	'fi' => 'fi',
	'sv' => 'se',
	'bg' => 'bg',
	'ro' => 'ro',
	'el' => 'gr',
	'he' => 'il',
#	'cz' => 'cz',
	'hi' => 'in',
	'pl' => 'pl',
	'sk' => 'sk',
	'hu' => 'hu',
	'kk' => 'kz',
	'vi' => 'vn',
	'et' => 'ee',
	'lt' => 'lt',
	'lv' => 'lv',
	'ko' => 'kp',
	'ja' => 'jp',
	'zh' => 'cn',
);
foreach ($lang_to_country as $lang => $country) {
	$data[$lang]['country'] = $country;
}

$f4 = dirname(__FILE__).'/languages.php';
file_put_contents($f4, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
print_r($data);
