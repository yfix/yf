#!/usr/bin/php
<?php

define('DB_TYPE', 'mysql5');
define('DB_HOST', 'localhost');
#define('DB_NAME', 'tmp_'.substr(md5(microtime().'bt6reedc5zw3'), 8));
define('DB_NAME', 'tmp_sql_to_php');
define('DB_USER', 'root');
define('DB_PSWD', '123456');
define('DB_PREFIX', 'tmp_');

exec('mysql -h '.escapeshellarg(DB_HOST).' -u '.escapeshellarg(DB_USER).' -p'.escapeshellarg(DB_PSWD).' -e "CREATE DATABASE IF NOT EXISTS '.DB_NAME.'"');

define('YF_PATH', '/home/www/yf/');
if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__DIR__))).'/');
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
	return 'CREATE TABLE IF NOT EXISTS '.DB_PREFIX. $table.' ('.$data.') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
}

mkdir('./data/', 0755, true);
foreach (glob(dirname(dirname(__DIR__)).'/install/install/sql/*.sql') as $f) {
	$fname = basename($f);
	$table = substr($fname, 0, -strlen('.sql'));
	$tname = DB_PREFIX. $table;
	if ($fname[0] == '_') {
		$lang = substr($fname, -strlen('.sql') - 2, -strlen('.sql'));
		$dir = './data_'.$lang.'/';
		mkdir($dir, 0755, true);
		foreach (explode(';'.PHP_EOL, file_get_contents($f)) as $sql) {
			if (preg_match('/%%prefix%%([a-z0-9_]+)/ims', $sql, $m)) {
				$t = trim($m[1]);
				$create_sql = get_table_create_sql($t);
				if (!$create_sql) {
					continue;
				}
				db()->query($create_sql);
				db()->query('TRUNCATE TABLE '.DB_PREFIX. $t);
				db()->query(str_replace('%%prefix%%', DB_PREFIX, $sql));
				$data = db()->get_all('SELECT * FROM '.DB_PREFIX. $t);
				if (!empty($data)) {
					file_put_contents($dir. $t.'.data.php', '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
				}
			}
		}
	} else {
		db()->query(get_table_create_sql($table));
		db()->query('TRUNCATE TABLE '.DB_PREFIX. $table);
		db()->query(str_replace('%%prefix%%', DB_PREFIX, file_get_contents($f)));
		$data = db()->get_all('SELECT * FROM '.$tname);
		if (!empty($data)) {
			file_put_contents('./data/'.$table.'.data.php', '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
		}
	}
}

#db()->query('DROP DATABASE IF EXISTS '.DB_NAME);
#exec('mysql -h '.escapeshellarg(DB_HOST).' -u '.escapeshellarg(DB_USER).' -p'.escapeshellarg(DB_PSWD).' -e "DROP DATABASE IF EXISTS '.DB_NAME.'"');
