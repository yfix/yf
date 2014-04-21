#!/usr/bin/php
<?php

require_once dirname(dirname(__FILE__)).'/scripts_init.php';

$force = trim($argv[2]);
$project_path = trim($argv[1]);
if (!$project_path) {
	exit('Error: missing project_path. Example: '.basename(__FILE__).' /home/www/test2/'.PHP_EOL);
}
$project_path = rtrim($project_path, '/').'/';
foreach (array('', '*/', '*/*/', '*/*/*/') as $g) {
	$paths = glob($project_path. $g. 'db_setup.php');
	if (!$paths || !isset($paths[0])) {
		continue;
	}
	$fp = $paths[0];
	if ($fp && file_exists($fp)) {
		require $fp;
		break;
	}
}
if (!defined('DB_NAME')) {
	exit('Error: cannot init database connection.');
}
require dirname(__FILE__).'/timezones.php';
if (!$data) {
	exit('Error: $data is missing');
}
$table = DB_PREFIX.'timezones';
$tables = db()->get_2d('show tables');
$table_exists = in_array($table, $table2);

$drop_table_sql = 'DROP TABLE IF EXISTS `'.$table.'`;'.PHP_EOL;
$create_table_sql = _get_create_table_sql('timezones');

$sql = db()->insert($table, _es($data), $only_sql = true);
if (!$table_exists || $force) {
#	echo $drop_table_sql;
	db()->query($drop_table_sql) or print_r(db()->error());
#	echo $create_table_sql;
	db()->query($create_table_sql) or print_r(db()->error());
}
#echo $sql.PHP_EOL;
db()->query($sql) or print_r(db()->error());

echo 'Trying to get 2 first records: '.PHP_EOL;
print_r(db()->get_all('SELECT * FROM '.$table.' LIMIT 2'));
