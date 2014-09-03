<?php

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_sqlite extends yf_db_utils_driver {

	/**
	*/
	function list_collations($extra = array()) {
		return true;
	}

	/**
	*/
	function list_charsets($extra = array()) {
		return true;
	}

	/**
	* @not_supported
	*/
	function list_databases($extra = array()) {
		return true;
	}

	/**
	* @not_supported
	*/
	function database_exists($db_name, $extra = array(), &$error = false) {
		return true;
	}

	/**
	* @not_supported
	*/
	function database_info($db_name = '', $extra = array(), &$error = OBfalse) {
		return true;
	}

	/**
	* @not_supported
	*/
	function create_database($db_name, $extra = array(), &$error = false) {
		return true;
	}

	/**
	* @not_supported
	*/
	function drop_database($db_name, $extra = array(), &$error = false) {
		return true;
	}

	/**
	* @not_supported
	*/
	function alter_database($db_name, $extra = array(), &$error = false) {
		return true;
	}

	/**
	* @not_supported
	*/
	function rename_database($db_name, $new_name, $extra = array(), &$error = false) {
		return true;
	}

	/**
	*/
	function list_tables($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$tables = $this->db->get_2d('SELECT name FROM sqlite_master WHERE type = "table" AND name <> "sqlite_sequence"');
		return $tables ? array_combine($tables, $tables) : array();
	}
}
