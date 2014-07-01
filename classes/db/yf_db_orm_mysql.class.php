<?php

/**
*/
load('db_orm_driver', 'framework', 'classes/db/');
class yf_db_orm_mysql extends yf_db_orm_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	*/
	function _load_model($name) {
// TODO
	}

	/**
	*/
	function _set_params($params = array()) {
// TODO
	}
}
