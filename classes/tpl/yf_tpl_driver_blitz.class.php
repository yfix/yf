<?php

/**
*/
class yf_tpl_driver_blitz {

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
		if ($params['string']) {
			$view = new Blitz();
			$view->load($params['string']);
			return $view->parse($replace);
		}
// TODO: test me and connect YF template loader
	}
}
