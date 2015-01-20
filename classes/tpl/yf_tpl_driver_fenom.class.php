<?php

/**
* Note: currently disabled, use this console command to add it back again:
* git submodule add https://github.com/yfix/fenom.git libs/fenom/
*/
class yf_tpl_driver_fenom {

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
		require_php_lib('fenom');
		$this->fenom = Fenom::factory('.', '/tmp', Fenom::AUTO_ESCAPE/* | Fenom::FORCE_COMPILE | Fenom::DISABLE_CACHE*/);
// TODO: fenom configuration
	}

	/**
	*/
	function parse($name, $replace = array(), $params = array()) {
		if ($params['string']) {
			$tpl = $this->fenom->compileCode($params['string'], $name);
			return $tpl->fetch($replace);
		}
// TODO: test me and connect YF template loader
		return $this->fenom->fetch($name.'.tpl', $replace);
	}
}
