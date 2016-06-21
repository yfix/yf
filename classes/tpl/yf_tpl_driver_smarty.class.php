<?php

/**
* Note: currently disabled, use this console command to add it back again:
* git submodule add https://github.com/yfix/smarty.git libs/smarty/
*/
class yf_tpl_driver_smarty {

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
		require_php_lib('smarty');
		$smarty = new Smarty();
		$smarty->setTemplateDir(YF_PATH. tpl()->TPL_PATH);
		$smarty->setCompileDir(STORAGE_PATH.'templates_c/');
		$smarty->setCacheDir(STORAGE_PATH.'smarty_cache/');
#		$smarty->setConfigDir(STORAGE_PATH.'smarty_configs/');
		$this->smarty = $smarty;
	}

	/**
	*/
	function parse($name, $replace = [], $params = []) {
		$this->smarty->assign($replace);
		if ($params['string']) {
			return $this->smarty->fetch('string:'.$params['string']);
		}
		return $this->smarty->display($name.'.tpl');
	}
}
