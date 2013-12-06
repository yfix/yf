<?php
$d_all = array();
foreach (glob(dirname(__FILE__).'/db_table_datas/*.db_table_data.php') as $f) {
	if (substr(basename($f), 0, strlen('sys_')) == 'sys_') {
		continue;
	} 
	require $f;
	$d_all = my_array_merge((array)$d_all, (array)$data);
}
$data = $d_all;
unset($d_all);
