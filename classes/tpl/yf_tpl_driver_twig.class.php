<?php

/**
*/
class yf_tpl_driver_twig {

	/**
	* Constructor
	*/
	function _init () {
		require_once YF_PATH.'libs/twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();
		$loader = new Twig_Loader_String();
		$twig = new Twig_Environment($loader);
		$this->twig = $twig;
// TODO: configure twig
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
			return $this->twig->render($params['string'], $replace);
		}
// TODO: test me and connect YF template loader
// TODO: enable parsing templates from files
	}
}
