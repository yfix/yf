<?php

/**
*/
class yf_tpl_driver_blitz {

	/**
	* Constructor
	*/
	function _init () {
		if (!class_exists('Blitz')) {
#			trigger_error(__CLASS__.': Blitz class not found, and it is required for this tpl driver.', E_USER_ERROR);
		}
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
		if (!class_exists('Blitz')) {
			return $params['string'];
		}
		if ($params['string']) {
			$view = new Blitz();
			$view->load($params['string']);
			return $view->parse($replace);
		}
// TODO: test me and connect YF template loader
	}
}
