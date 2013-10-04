<?php

/**
*/
class yf_tpl_driver_smarty {

	/**
	* Constructor
	*/
	function _init () {
// TODO
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function parse($name, $replace = array(), $params = array()) {
// TODO
	}
}
