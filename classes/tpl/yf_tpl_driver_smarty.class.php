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
		$smarty->setCompileDir(STORAGE_PATH.'templates_c/');
		$smarty->setCacheDir(STORAGE_PATH.'smarty_cache/');
#		$smarty->setConfigDir(STORAGE_PATH.'smarty_configs/');
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
		$this->smarty->assign($replace);
		if ($params['string']) {
			return $this->smarty->fetch('string:'.$params['string']);
		}
		return $this->smarty->display($name.'.tpl');
	}
}
