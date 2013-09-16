<?php

define('DB_TYPE',       "mysql41");
define('DB_HOST',       "localhost");
define('DB_NAME',       "yft3");
define('DB_USER',       "root");
define('DB_PSWD',       "123456");
define('DB_PREFIX',     "t_");
define('DB_CHARSET',    "utf8");
// Means that we use this default connection to connect to mysql slave in production, so no data changed allowed, use master instead
define('DB_REPLICATION_SLAVE',  false);

define('DB_HOST_PF',    "central.central.t3.yfix.net");
define('DB_NAME_PF',    "pf_admin");
define('DB_USER_PF',    DB_USER);
define('DB_PSWD_PF',    DB_PSWD);
define('DB_PREFIX_PF',  "pf_");
define('DB_CHARSET_PF', DB_CHARSET);

define('DB_HOST_RR',    "regreader.t3.yfix.net");
define('DB_NAME_RR',    "regreader2");
define('DB_USER_RR',    DB_USER);
define('DB_PSWD_RR',    DB_PSWD);
define('DB_PREFIX_RR',  "");
define('DB_CHARSET_RR', DB_CHARSET);

define('DB_HOST_CR',    "central.t3.yfix.net");
define('DB_NAME_CR',    "crawler_panel");
define('DB_USER_CR',    DB_USER);
define('DB_PSWD_CR',    DB_PSWD);
define('DB_PREFIX_CR',  "c_");
define('DB_CHARSET_CR', DB_CHARSET);

define('DB_HOST_SLAVE',     "localhost");
define('DB_NAME_SLAVE',     DB_NAME);
define('DB_USER_SLAVE',     DB_USER);
define('DB_PSWD_SLAVE',     DB_PSWD);
define('DB_PREFIX_SLAVE',   DB_PREFIX);
define('DB_CHARSET_SLAVE',  DB_CHARSET);

define('DB_HOST_MASTER',    "central.t3.yfix.net");
define('DB_NAME_MASTER',    DB_NAME);
define('DB_USER_MASTER',    DB_USER);
define('DB_PSWD_MASTER',    DB_PSWD);
define('DB_PREFIX_MASTER',  DB_PREFIX);
define('DB_CHARSET_MASTER', DB_CHARSET);

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
function db_t3($tbl_name = "") {
	return db($tbl_name);
}
function db_t2($tbl_name = "") {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class("mysql5", 1, DB_PREFIX_T2);
			$_instance->connect(DB_HOST_T2, DB_USER_T2, DB_PSWD_T2, DB_NAME_T2, true);
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
function db_pf($tbl_name = "") {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class("mysql5", 1, DB_PREFIX_PF);
			$_instance->connect(DB_HOST_PF, DB_USER_PF, DB_PSWD_PF, DB_NAME_PF, true);
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
function db_rr($tbl_name = "") {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class("mysql5", 1, DB_PREFIX_RR);
			$_instance->connect(DB_HOST_RR, DB_USER_RR, DB_PSWD_RR, DB_NAME_RR, true);
		} else {
			$_instance = false;
		}
	}
	if (!is_object($_instance)) {
		return $tbl_name ? $tbl_name : new my_missing_method_handler(__FUNCTION__);
	}
	return $tbl_name ? $_instance->_real_name($tbl_name) : $_instance;
}
function db_cr($tbl_name = "") {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class("mysql5", 1, DB_PREFIX_CR);
			$_instance->connect(DB_HOST_CR, DB_USER_CR, DB_PSWD_CR, DB_NAME_CR, true);
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
function db_m3($tbl_name = "") {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class("mysql5", 1, DB_PREFIX_MASTER);
			$_instance->connect(DB_HOST_MASTER, DB_USER_MASTER, DB_PSWD_MASTER, DB_NAME_MASTER, true);
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
function db_master($tbl_name = "") {
	return db_m3($tbl_name);
}
function db_slave($tbl_name = "") {
	return db($tbl_name);
}

define("YF_PATH", dirname(dirname(dirname(dirname(__FILE__))))."/");
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", 1, 0);

######################
echo "db():\n".			print_r( db()->get_one('SELECT COUNT(*) AS num from '.db('user').' ') , 1);
echo "db_t2():\n".		print_r( db_t2()->get_one('SELECT COUNT(*) AS num from '.db_t2('user').' ') , 1);
echo "db_t3():\n".		print_r( db_t3()->get_one('SELECT COUNT(*) AS num from '.db_t3('user').' ') , 1);
echo "db_rr():\n".		print_r( db_rr()->get_one('SELECT COUNT(*) AS num from '.db_rr('user').' ') , 1);
echo "db_cr():\n".		print_r( db_cr()->get_one('SELECT COUNT(*) AS num from '.db_cr('user').' ') , 1);
echo "db_m3():\n".		print_r( db_m3()->get_one('SELECT COUNT(*) AS num from '.db_m3('user').' ') , 1);
echo "db_master():\n".	print_r( db_master()->get_one('SELECT COUNT(*) AS num from '.db_master('user').' ') , 1);
echo "db_slave():\n".	print_r( db_slave()->get_one('SELECT COUNT(*) AS num from '.db_slave('user').' ') , 1);
