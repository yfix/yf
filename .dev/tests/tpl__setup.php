<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

abstract class tpl_abstract extends PHPUnit_Framework_TestCase {
	public static $_bak = array();
	public static function setUpBeforeClass() {
		tpl()->INSIDE_UNIT_TESTS = true;
		if (false !== strpos(strtolower(get_called_class()), 'compiled')) {
			self::$_bak = tpl()->COMPILE_TEMPLATES;
			tpl()->COMPILE_TEMPLATES = true;
		}
	}
	public static function tearDownAfterClass() {
		if (false !== strpos(strtolower(get_called_class()), 'compiled')) {
			tpl()->COMPILE_TEMPLATES = self::$_bak;
			_class('dir')->delete_dir('./stpls_compiled/', $delete_start_dir = true);
		}
	}
	public function _tpl($stpl_text = '', $replace = array(), $name = '', $params = array()) {
		if (!$name) {
			$name = 'auto__'.get_called_class().'__'.substr(md5($stpl_text), 0, 16);
		}
		return tpl()->parse_string($stpl_text, $replace, $name, $params);
	}
}
