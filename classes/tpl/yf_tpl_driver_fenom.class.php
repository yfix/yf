<?php

/**
*/
class yf_tpl_driver_fenom {

	/**
	* Constructor
	*/
	function _init () {
#		$fenom_dir = YF_PATH. 'libs/fenom/src/';
		require_once $fenom_dir. 'Fenom.php';
#		$fenom = Fenom::factory('.', '/tmp', Fenom::AUTO_ESCAPE);;
		$fenom = new Fenom();
var_dump($fenom);
#		$template = $fenom->compileCode('Hello {$user.name}! {if $user.email?} Your email: {$user.email} {/if}');
#		$template->display($data);
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
