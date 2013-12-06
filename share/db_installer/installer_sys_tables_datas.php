<?php
$d_all = array();
foreach (glob(dirname(__FILE__).'/db_table_datas/sys_*.db_table_data.php') as $f) {
	require $f;
	$d_all = my_array_merge((array)$d_all, (array)$data);
}
$data = $d_all;
unset($d_all);
