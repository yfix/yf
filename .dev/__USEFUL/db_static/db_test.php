<?php

define("INCLUDE_PATH", "/home/www/toggle3/public_html/");
define("PF_PATH", "/home/www/yf/");
require_once INCLUDE_PATH."db_setup.php";
#require_once PF_PATH."classes/profy_db_static.class.php";
require_once "./profy_db_static.class.php";

// Required to catch missing methods of the shortcut functions objects
// Only one class from functions listed below
if (!class_exists('my_missing_method_handler')) {
	class my_missing_method_handler {
		function __construct($o_name) { $this->_o_name = $o_name; }
		function __call($name, $arguments) { trigger_error($this->_o_name.'(): missing object method: '.$name, E_USER_WARNING); return false; }
	}
}
function load_db_class() {
	static $_loaded_class;
	if ($_loaded_class) {
		return $_loaded_class;
	}
	$classes = array(
		'db'		=> INCLUDE_PATH.'classes/db.class.php',
		'profy_db'	=> PF_PATH.'classes/profy_db.class.php',
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
function db_pf($tbl_name = "") {
	static $_instance;
	if (is_null($_instance)) {
		$db_class = load_db_class();
		if ($db_class) {
			$_instance = new $db_class("mysql5", 1, DB_PREFIX_PF);
			$_instance->connect(DB_HOST_PF, DB_USER_PF, DB_PSWD_PF, DB_NAME_PF, true);
		} else {
			$_instance = false;
		}
	}
	if (!is_object($_instance)) {
		return $tbl_name ? $tbl_name : new my_missing_method_handler(__FUNCTION__);
	}
	return $tbl_name ? $_instance->_real_name($tbl_name) : $_instance;
}
########################

class db_pf extends profy_db_static {}
db_pf::set_params(array("HOST" => DB_HOST_PF, "USER" => DB_USER_PF, "PSWD" => DB_PSWD_PF, "NAME" => DB_NAME_PF));

class db_t2 extends profy_db_static {}
db_t2::set_params(array("HOST" => DB_HOST_T2, "USER" => DB_USER_T2, "PSWD" => DB_PSWD_T2, "NAME" => DB_NAME_T2));

class db_master extends profy_db_static {}
db_master::set_params(array("HOST" => DB_HOST_MASTER, "USER" => DB_USER_MASTER, "PSWD" => DB_PSWD_MASTER, "NAME" => DB_NAME_MASTER));

########################

print_r(db_pf()->get("SELECT * FROM pf_servers LIMIT 1"));
print_r(db_pf::get("SELECT * FROM pf_servers LIMIT 1"));
print_r(db_t2::get("SELECT * FROM ircfast_versions LIMIT 1"));
print_r(db_master::get("SELECT * FROM t_versions LIMIT 1"));
