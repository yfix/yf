<?php

/**
*/
class yf_tpl_driver_fenom {

	/**
	* Constructor
	*/
	function _init () {
		$fenom_dir = YF_PATH. 'libs/fenom/src/';
		require_once $fenom_dir. 'Fenom.php';
		// Dirty autoload
		foreach (glob($fenom_dir.'Fenom/*.php') + glob($fenom_dir.'Fenom/Error/*.php') as $f) {
			require_once $f;
		}
		$fenom = Fenom::factory('.', '/tmp', Fenom::AUTO_ESCAPE/* | Fenom::FORCE_COMPILE | Fenom::DISABLE_CACHE*/);
		$this->fenom = $fenom;
// TODO: fenom configuration
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
			$tpl = $this->fenom->compileCode($params['string'], $name);
			return $tpl->fetch($replace);
		}
// TODO: test me and connect YF template loader
		return $this->fenom->fetch($name.'.tpl', $replace);
	}
}
