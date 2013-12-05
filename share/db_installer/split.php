<?php

if (!function_exists('my_array_merge')) {
	function my_array_merge($a1, $a2) {
		foreach ((array)$a2 as $k => $v) { if (isset($a1[$k]) && is_array($a1[$k])) { if (is_array($a2[$k])) { 
			foreach ((array)$a2[$k] as $k2 => $v2) { if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) { $a1[$k][$k2] += $v2; } else { $a1[$k][$k2] = $v2; } 
		} } else { $a1[$k] += $v; } } else { $a1[$k] = $v; } }
		return $a1;
	}
}

##############

$d = dirname(__FILE__).'/db_table_sql/';
if (!file_exists($d)) {
	mkdir($d, 0755, true);
}
foreach (glob(dirname(__FILE__).'/installer*_structs.php') as $_file) {
	$data = array();
	require_once $_file;
	foreach ((array)$data as $k => $v) {
		if (false !== strpos($_file, '_sys_')) {
			$k = 'sys_'.$k;
		}
		$f = $d. $k.'.db_table_sql.php';
		file_put_contents($f, '<'.'?php'.PHP_EOL.'$data = '.var_export($v, 1).';');
	}
}

###############

$d = dirname(__FILE__).'/db_table_datas/';
if (!file_exists($d)) {
	mkdir($d, 0755, true);
}
foreach (glob(dirname(__FILE__).'/installer*_datas.php') as $_file) {
	$data = array();
	require_once $_file;
	foreach ((array)$data as $k => $v) {
		if (false !== strpos($_file, '_sys_')) {
			$k = 'sys_'.$k;
		}
		$f = $d. $k.'.db_table_data.php';
		file_put_contents($f, '<'.'?php'.PHP_EOL.'$data = '.var_export($v, 1).';');
	}
}
