<?php

/**
* Note: currently disabled, use this console command to add it back again:
* git submodule add https://github.com/yfix/twig.git libs/Twig/
*/
class yf_tpl_driver_twig {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _init () {
		require_php_lib('twig');
		$this->twig = new Twig_Environment( new Twig_Loader_String() );
// TODO: configure twig
	}

	/**
	*/
	function parse($name, $replace = [], $params = []) {
		if ($params['string']) {
			return $this->twig->render($params['string'], $replace);
		}
// TODO: test me and connect YF template loader
// TODO: enable parsing templates from files
	}
}
