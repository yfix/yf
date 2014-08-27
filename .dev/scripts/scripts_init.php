<?php

$force = trim($argv[2]);
$project_path = trim($argv[1]);
if (!$project_path) {
	exit('Error: missing project_path. Example: '.basename($argv[0]).' /home/www/test2/'.PHP_EOL);
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
#####
$PROJECT_CONF['db']['RECONNECT_NUM_TRIES'] = 1;
$PROJECT_CONF['db']['FIX_DATA_SAFE'] = 0;
$_GET['object'] = 'not_exists';
#####
if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(__DIR__)).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = false);
	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
}
#####
if (!defined('DB_NAME_GEONAMES')) {
	define('DB_PREFIX_GEONAMES', '');
	define('DB_HOST_GEONAMES', getenv('YF_DB_HOST') ?: 'localhost');
	define('DB_USER_GEONAMES', getenv('YF_DB_USER') ?: 'root');
	define('DB_PSWD_GEONAMES', is_string(getenv('YF_DB_PSWD')) ? getenv('YF_DB_PSWD') : '123456');
	define('DB_NAME_GEONAMES', 'geonames');
}
function db_geonames($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', DB_PREFIX_GEONAMES);
			$_instance->DB_PREFIX = DB_PREFIX_GEONAMES;
			$_instance->connect(DB_HOST_GEONAMES, DB_USER_GEONAMES, DB_PSWD_GEONAMES, DB_NAME_GEONAMES, true);
		} else {
			$_instance = false;
		}
	}
	if (!is_object($_instance)) {
		return $tbl_name ? $tbl_name : new my_missing_method_handler(__FUNCTION__);
	}
	return $tbl_name ? $_instance->_real_name($tbl_name) : $_instance;
}
