<?php

if (!defined('YF_PATH')) {
	define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
	require YF_PATH.'classes/yf_main.class.php';
	new yf_main('admin', $no_db_connect = false, $auto_init_all = true);
	ini_set('display_errors', 'on');
	error_reporting(E_ALL ^ E_NOTICE);
}

function _get_create_table_sql($tname) {
	include (YF_PATH. 'share/db_installer/sql/'.$tname.'.sql.php');
	return 'CREATE TABLE IF NOT EXISTS `'.$table.'` ('.$data.') ENGINE=InnoDB DEFAULT CHARSET=utf8;'.PHP_EOL;
}
function load_db_class() {
	static $_loaded_class;
	if ($_loaded_class) {
		return $_loaded_class;
	}
	$classes = array(
		'db'	=> INCLUDE_PATH.'classes/db.class.php',
		'yf_db'	=> YF_PATH.'classes/yf_db.class.php',
	);
	foreach ((array)$classes as $cl => $f) {
		if (!file_exists($f)) {
			continue;
		}
		require_once $f;
		if (class_exists($cl)) {
			$_loaded_class = $cl;
			return $_loaded_class;
		}
	}
	return false;
}
if (!defined('DB_NAME_GEONAMES')) {
	define('DB_PREFIX_GEONAMES', '');
	define('DB_HOST_GEONAMES', 'localhost');
	define('DB_USER_GEONAMES', 'root');
	define('DB_PSWD_GEONAMES', '123456');
	define('DB_NAME_GEONAMES', 'geonames');
}
function db_geonames($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_GEONAMES);
			$_instance->connect(DB_HOST_GEONAMES, DB_USER_GEONAMES, DB_PSWD_GEONAMES, DB_NAME_GEONAMES, true);
			$_instance->_parse_tables();
		} else {
			$_instance = false;
		}
	}
	if (!is_object($_instance)) {
		return $tbl_name ? $tbl_name : new my_missing_method_handler(__FUNCTION__);
	}
	return $tbl_name ? $_instance->_real_name($tbl_name) : $_instance;
}
