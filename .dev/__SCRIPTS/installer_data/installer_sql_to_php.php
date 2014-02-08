#!/usr/bin/php
<?php

define('DB_HOST', 'localhost');
#define('DB_NAME', 'tmp_'.substr(md5(microtime().'bt6reedc5zw3'), 8));
define('DB_NAME', 'tmp_123');
define('DB_USER', 'root');
define('DB_PSWD', '123456');
define('DB_PREFIX', 'tmp_');

exec('mysql -h '.escapeshellarg(DB_HOST).' -u '.escapeshellarg(DB_USER).' -p'.escapeshellarg(DB_PSWD).' -e "CREATE DATABASE IF NOT EXISTS '.DB_NAME.'"');

define('YF_PATH', '/home/www/yf/');
if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(dirname(__FILE__)))).'/');
}
if (!function_exists('main')) {
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('user', $no_db_connect = false, $auto_init_all = true);
}

#db()->query('CREATE DATABASE IF NOT EXISTS '.DB_NAME);

function get_table_create_sql($table) {
	$path = YF_PATH. 'share/db_installer/db_table_sql/'.$table.'.db_table_sql.php';
	include $path;
	if (!$data) {
		return false;
	}
	return 'CREATE TABLE IF NOT EXISTS '.DB_PREFIX. $table.' ('.$data.')';
}

foreach (glob(dirname(dirname(dirname(__FILE__))).'/__INSTALL/install/sql/*.sql') as $f) {
	$fname = basename($f);
	$table = substr($fname, 0, -strlen('.sql'));
	$tname = DB_PREFIX. $table;
	if ($fname[0] == '_') {
// TODO
		continue;
	} else {
		echo get_table_create_sql($table);
		db()->query(get_table_create_sql($table));
		$sql = str_replace('%%prefix%%', DB_PREFIX, file_get_contents($f));
		db()->query($sql)
 or print db()->error();
		$data = db()->get_all('SELECT * FROM '.$tname);
var_export($data);
	}
}

#db()->query('DROP DATABASE IF EXISTS '.DB_NAME);
#exec('mysql -h '.escapeshellarg(DB_HOST).' -u '.escapeshellarg(DB_USER).' -p'.escapeshellarg(DB_PSWD).' -e "DROP DATABASE IF EXISTS '.DB_NAME.'"');
