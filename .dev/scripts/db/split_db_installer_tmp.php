<?php

exit('Script is temporary');

##############

$d = __DIR__.'/db_table_sql/';
if (!file_exists($d)) {
	mkdir($d, 0755, true);
}
foreach (glob(__DIR__.'/installer*_structs.php') as $_file) {
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

$d = __DIR__.'/db_table_datas/';
if (!file_exists($d)) {
	mkdir($d, 0755, true);
}
foreach (glob(__DIR__.'/installer*_datas.php') as $_file) {
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
