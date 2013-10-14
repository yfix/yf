#!/usr/bin/php
<?php

$url = 'http://www.currency-iso.org/dam/downloads/table_a1.xml';
$f = dirname(__FILE__).'/'.basename($url);
if (!file_exists($f)) {
	file_put_contents($f, file_get_contents($url));
}
$xml_string = file_get_contents($f);
$xml = simplexml_load_string($xml_string);
$data = array();
foreach ($xml->CcyTbl->CcyNtry as $item) {
	$item = (array)$item;
	$id = $item['Ccy'];
	if (!$id) {
		continue;
	}
	$data[$id] = array(
		'id'	=> $item['Ccy'],
		'name'	=> $item['CcyNm'],
		'number'=> (int)$item['CcyNbr'],
		'minor_units' => (int)$item['CcyMnrUnts'],
		'country_name' => $item['CtryNm'],
		'country_code' => '', // TODO
		'sign'	=> '', // TODO
		'active' => '0',
	);
}
#############
$url2 = 'https://en.wikipedia.org/wiki/List_of_circulating_currencies';
$f3 = dirname(__FILE__).'/'.basename($url2).'.table.html';
if (!file_exists($f3)) {
	$html2 = file_get_contents($url2);
	$regex2 = '~<h2>[^<]*<span[^>]*id="List_of_circulating_currencies_by_country_or_territory"[^>]*>.*?</h2>[^<]*<table[^>]*>(.*?)</table>~ims';
	preg_match($regex2, $html2, $m2);
	file_put_contents($f3, $m2[1]);
}
$html3 = file_get_contents($f3);
$regex31 = '~<tr[^>]*>(.*?)</tr>~ims';
preg_match_all($regex31, $html3, $m31);
$tmp_tbl = array();
foreach ($m31[1] as $v31) {
	$regex32 = '~<td[^>]*>(.*?)</td>~ims';
	preg_match_all($regex32, $v31, $m32);
	$tmp_tbl[] = $m32[1];
}
#############
foreach($tmp_tbl as $v) {
	$id = '';
	$sign = '';
	if (count($v) == 5) {
		$id = $v[2];
		$sign = $v[1];
	} elseif (count($v) == 6) {
		$id = $v[3];
		$sign = $v[2];
	}
	if (!$id) {
		continue;
	}
	if ($sign) {
		if (strlen($sign) > 7 || false !== strpos($sign, '<')) {
			$sign = '';
		}
		if (false !== strpos($sign, ' or ')) {
			$sign = current(explode(' or ', $sign));
		}
	}
	if ($id && $sign && isset($data[$id])) {
		$data[$id]['sign'] = $sign;
	}
}
foreach (array('USD','EUR','CHF','JPY','UAH','RUB') as $c) {
	$data[$c]['active'] = 1;
}

$f4 = dirname(__FILE__).'/currencies.php';
file_put_contents($f4, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
print_r($data);
