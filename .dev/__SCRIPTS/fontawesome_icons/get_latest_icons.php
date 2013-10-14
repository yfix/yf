#!/usr/bin/php
<?php
/*
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
*/
$url = 'http://fontawesome.io/cheatsheet/';
$f2 = dirname(__FILE__).'/'.basename($url);
if (!file_exists($f2)) {
	$html1 = file_get_contents($url);
	$regex1 = '~<h2[^>]*page-header[^>]*>.*?</h2>[^>]*<div class="row">(.*?</div>)[^>]*</div>~ims';
	preg_match($regex1, $html1, $m1);
	file_put_contents($f2, $m1[1]);
}
$html2 = file_get_contents($f2);
#############
$regex2 = '~</i>[^<]*(icon\-[^<]*)<~ims';
$tmp = array();
preg_match_all($regex2, $html2, $m2);
foreach($m2[1] as $v) {
	$v = trim($v);
	$tmp[$v] = $v;
}
ksort($tmp);
#############
$data = array();
foreach ($tmp as $v) {
	$id = $v;
	if (!$id) {
		continue;
	}
	$data[$id] = array(
		'name'	=> $id,
		'active'=> 0,
	);
}
$f4 = dirname(__FILE__).'/fontawesome_icons.php';
file_put_contents($f4, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
print_r($data);
