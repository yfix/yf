#!/usr/bin/php
<?php

$force = trim($argv[2]);
$project_path = trim($argv[1]);
if (!$project_path) {
	exit('Error: missing project_path. Example: '.basename(__FILE__).' /home/www/test2/'.PHP_EOL);
}
$project_path = rtrim($project_path, '/').'/';
$fp = exec('find '.escapeshellarg($project_path).' -name "db_setup.php"');
if ($fp && file_exists($fp)) {
	require $fp;
}
if (!defined('DB_NAME')) {
	exit('Error: cannot init database connection.');
}
require dirname(__FILE__).'/currencies.php';
if (!$data) {
	exit('Error: $data is missing');
}
###########
if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(dirname(__FILE__)))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = true);
}
###########
$table = DB_PREFIX.'currencies';
$tables = db()->get_2d('show tables');
$table_exists = in_array($table, $table2);

$drop_table_sql = "DROP TABLE IF EXISTS `".$table."`;".PHP_EOL;
$create_table_sql = "CREATE TABLE IF NOT EXISTS `".$table."` (
  `id` char(3) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `sign` varchar(32) NOT NULL DEFAULT '',
  `number` int(10) NOT NULL DEFAULT '0',
  `minor_units` int(2) NOT NULL DEFAULT '0',
  `country_name` varchar(64) NOT NULL DEFAULT '',
  `country_code` char(2) NOT NULL DEFAULT '',
  `continent_code` char(2) NOT NULL DEFAULT '',
  `active` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;".PHP_EOL;

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
