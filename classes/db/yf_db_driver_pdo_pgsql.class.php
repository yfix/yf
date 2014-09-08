<?php

load('db_driver_pdo', 'framework', 'classes/db/');
class yf_db_driver_pdo_pgsql extends yf_db_driver_pdo {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}
	// TODO
}
