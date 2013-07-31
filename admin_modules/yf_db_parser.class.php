<?php

/**
* Class that parse db structure and allow to add/update/delete records
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_db_parser {

	/**
	*/
	function show() {
		$table = $this->_get_table_name();
		if (!$table) {
			return _e('Wrong params');
		}
		return table2('SELECT * FROM '.db($table), array('links_add' => '&table='.$table))
			->auto();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET["id"]);
		$table = $this->_get_table_name();
		if (!$id || !$table) {
			return _e('Wrong params');
		}
		$replace = _class('admin_methods')->edit(array('table' => $table, 'links_add' => '&table='.$table));
		return form2($replace)
			->auto(db($table), $id, array('links_add' => '&table='.$table));
	}

	/**
	*/
	function add() {
		$table = $this->_get_table_name();
		if (!$table) {
			return _e('Wrong params');
		}
		$replace = _class('admin_methods')->add(array('table' => $table, 'links_add' => '&table='.$table));
		return form2($replace)
			->auto(db($table), $id, array('links_add' => '&table='.$table));
	}

	/**
	*/
	function delete() {
		$id = intval($_GET["id"]);
		$table = $this->_get_table_name();
		if (!$id || !$table) {
			return _e('Wrong params');
		}
		return _class('admin_methods')->delete(array('table' => $table, 'links_add' => '&table='.$table));
	}

	/**
	*/
	function _get_table_name($table = "") {
		if (!$table) {
			$table = $_GET['table'];
		}
		return preg_replace('/[^a-z0-9_]+/ims', '', $table);
	}
}
