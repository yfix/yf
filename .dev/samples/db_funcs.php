<?php

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
function db_t3($tbl_name = '') {
	return db($tbl_name);
}
function db_t2($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_T2);
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
function db_pf($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_PF);
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
function db_rr($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_RR);
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
function db_cr($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_CR);
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
function db_m3($tbl_name = '') {
	$_instance = &$GLOBALS[__FUNCTION__];
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class('mysql5', 1, DB_PREFIX_MASTER);
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
function db_master($tbl_name = '') {
	return db_m3($tbl_name);
}
function db_slave($tbl_name = '') {
	return db($tbl_name);
}
