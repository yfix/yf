<?php

// TODO: remove me
define('YF_PATH', '/home/www/yf/');

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
###########

if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(dirname(__FILE__)))).'/');
}
if (!function_exists('main')) {
	require YF_PATH.'classes/yf_main.class.php';
#	new yf_main('admin', $no_db_connect = false, $auto_init_all = true);
	new yf_main('user', $no_db_connect = false, $auto_init_all = true);
}
###########

mkdir('./sql/', 0755, true);
mkdir('./data/', 0755, true);
$db_tables_like = $db_tables_like ?: '%';
foreach((array)db()->get_2d('SHOW TABLES LIKE "'.DB_PREFIX.$db_tables_like.'"') as $table) {
	$tname = substr($table, strlen(DB_PREFIX));
	$db_create_sql = current(db()->get_2d('SHOW CREATE TABLE '.$table));
	$p1 = strpos($db_create_sql, '(') + 1;
	$p2 = strrpos($db_create_sql, ')');
	$db_create_sql = trim(substr($db_create_sql, $p1, $p2 - $p1));
	$db_create_sql = str_replace('  ', "\t", '  '.$db_create_sql);
	$file_sql = './sql/'.$tname.'.sql.php';
	echo $file_sql. PHP_EOL;
	file_put_contents($file_sql, '<?'.'php'.PHP_EOL.'$data = \''.PHP_EOL.addslashes($db_create_sql).PHP_EOL.'\';');
	if (false !== strpos($table, 'sys_log_') || false !== strpos($table, '_revisions')) {
		continue;
	}
	$data = db()->get_all('SELECT * FROM '.$table);
	if (empty($data)) {
		continue;
	}
	$file_data = './data/'.$tname.'.data.php';
	echo $file_data. PHP_EOL;
	file_put_contents($file_data, '<?'.'php'.PHP_EOL.'$data = '.var_export($data, 1).';');
}