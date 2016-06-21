<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

abstract class tpl_abstract extends yf_unit_tests {
	public static $_bak = [];
	public static function setUpBeforeClass() {
		// Replace default style and script templates with empty strings
		tpl()->parse_string('', [], 'style_css');
		tpl()->parse_string('', [], 'script_js');

		tpl()->INSIDE_UNIT_TESTS = true;
		if (false !== strpos(strtolower(get_called_class()), 'compiled')) {
			self::$_bak = tpl()->COMPILE_TEMPLATES;
			tpl()->COMPILE_TEMPLATES = true;
			_class('dir')->mkdir(STORAGE_PATH.'stpls_compiled/');
		}
		common()->USER_ERRORS = [];
	}
	public static function tearDownAfterClass() {
		if (false !== strpos(strtolower(get_called_class()), 'compiled')) {
			tpl()->COMPILE_TEMPLATES = self::$_bak;
			_class('dir')->delete_dir(STORAGE_PATH.'stpls_compiled/', $delete_start_dir = true);
		}
	}
	public function _tpl($stpl_text = '', $replace = [], $name = '', $params = []) {
		if (!$name) {
			$name = 'auto__'.get_called_class().'__'.substr(md5($stpl_text), 0, 16);
		}
		return tpl()->parse_string($stpl_text, $replace, $name, $params);
	}
}
