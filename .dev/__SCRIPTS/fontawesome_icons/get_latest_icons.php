#!/usr/bin/php
<?php

function data_get_latest_icons() {
	$url = 'http://fontawesome.io/cheatsheet/';
	$f2 = dirname(__FILE__).'/'.basename($url);
	if (!file_exists($f2)) {
		$html1 = file_get_contents($url);
		$regex1 = '~<h2[^>]*page-header[^>]*>.*?</h2>[^>]*<div class="row">(.*?</div>)[^>]*</div>~ims';
		preg_match($regex1, $html1, $m1);
		file_put_contents($f2, $m1[1]);
	}
	$html2 = file_get_contents($f2);
	///////////////
	$regex2 = '~</i>[^<]*(icon\-[^<]*)<~ims';
	$tmp = array();
	preg_match_all($regex2, $html2, $m2);
	foreach($m2[1] as $v) {
		$v = trim($v);
		$tmp[$v] = $v;
	}
	ksort($tmp);
	///////////////
	$data = array();
	foreach ($tmp as $v) {
		$id = $v;
		if (!$id) {
			continue;
		}
		$data[$id] = array(
			'name'	=> $id,
			'active'=> 1,
		);
	}
	//foreach (range(1,20) as $c) {
	//	$data[$c]['active'] = 1;
	//}

	$f4 = dirname(__FILE__).'/fontawesome_icons.php';
	file_put_contents($f4, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
	print_r($data);
}

data_get_latest_icons();
