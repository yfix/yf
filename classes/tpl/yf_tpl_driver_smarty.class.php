<?php

/**
*/
class yf_tpl_driver_smarty {

	/**
	* Constructor
	*/
	function _init () {
		require_once YF_PATH. 'libs/smarty/libs/Smarty.class.php';
		$smarty = new Smarty();
		$smarty->setTemplateDir(YF_PATH. tpl()->TPL_PATH);
#		$smarty->setCompileDir('/web/www.example.com/guestbook/templates_c/');
#		$smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
#		$smarty->setCacheDir('/web/www.example.com/guestbook/cache/');
		$this->smarty = $smarty;
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
		foreach ((array)$replace as $k => $v) {
			$this->smarty->assign($k, $v);
		}
		if ($params['string']) {
			return $this->smarty->fetch('string:'.$params['string']);
		}
	}
}
