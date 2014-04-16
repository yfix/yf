#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_utils.php';

// TODO

$url = $url ?: 'https://www.cia.gov/library/publications/the-world-factbook/fields/2098.html';
$result_file = $result_file ?: dirname(__FILE__).'/countries.php';
$suffix = $suffix ?: '';

if (!function_exists('data_get_latest_lang_by_country')) {
function data_get_latest_lang_by_country() {
	global $url, $result_file, $suffix;

	$f2 = dirname(__FILE__).'/'.basename($url).'.table'.$suffix.'.html';
	if (!file_exists($f2)) {
		$html1 = file_get_contents($url);
		$regex1 = '~<article class="description-box">(.+?)</article>~ims';
		preg_match($regex1, $html1, $m1);
		$html1 = $m1[1];
		$html1 = preg_replace('~<script[^>]*?>.*?</script>~ims', '', $html1);
		$html1 = preg_replace('~<style[^>]*?>.*?</style>~ims', '', $html1);
		$html1 = preg_replace('~<select[^>]*?>.*?</select>~ims', '', $html1);
		preg_match_all('~<table[^>]*id="[a-z]{2}"[^>]*>.+?</table>~ims', $html1, $m1_2);
		// Strip empty lines inside matches
		$html1 = implode(PHP_EOL.PHP_EOL, preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $m1_2[0]));
		// Strip multiple whitespaces/tabs
		$html1 = preg_replace('~[ \t]{2,}~im', ' ', $html1);
		file_put_contents($f2, $html1);
	}
	$html2 = file_get_contents($f2);

	preg_match_all('~<table[^>]*id="(?P<code>[a-z]{2})"[^>]*>.*?<td[^>]*class="category_data"[^>]*>(?P<data>.+?)</td>.*?</table>~ims', $html2, $m2);
	$data_tmp = array();
	foreach ((array)$m2[0] as $k => $tmp) {
		$data_tmp[$m2['code'][$k]] = trim(strip_tags($m2['data'][$k]));
	}
	$data = array();
	foreach ((array)$data_tmp as $code => $text) {
		preg_match_all('~([a-z]+)\s*\(official[^\)]*\)~ims', $text, $m3);
		if ($m3[1]) {
			$data[$code] = $m3[1];
		}
	}
print_r($data);
#	$tmp_tbl = html_table_to_array($html2);
#print_r($tmp_tbl);
/*
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

data_get_latest_lang_by_country();