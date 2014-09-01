<?php

if (!defined('DB_NAME_GEONAMES')) {
	define('DB_PREFIX_GEONAMES', '');
	define('DB_HOST_GEONAMES', 'localhost');
	define('DB_USER_GEONAMES', 'root');
	define('DB_PSWD_GEONAMES', '123456');
	define('DB_NAME_GEONAMES', 'geonames');
}
if (!defined('DB_NAME')) {
	define('DB_TYPE', 'mysql5');
	define('DB_PREFIX', DB_PREFIX_GEONAMES);
	define('DB_HOST', DB_HOST_GEONAMES);
	define('DB_USER', DB_USER_GEONAMES);
	define('DB_PSWD', DB_PSWD_GEONAMES);
	define('DB_NAME', DB_NAME_GEONAMES);
}
function db_geonames($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_GEONAMES);
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
#####
$PROJECT_CONF['db']['RECONNECT_NUM_TRIES'] = 1;
$PROJECT_CONF['db']['FIX_DATA_SAFE'] = 0;
$_GET['object'] = 'not_exists';
#####
if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__DIR__))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = false);
	date_default_timezone_set('Europe/Kiev');
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
}
#####
