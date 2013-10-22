<?php

require_once dirname(__FILE__).'/tpl_driver_yf_bugs.Test.php';

class tpl_driver_yf_bugs_compiled_test extends tpl_driver_yf_bugs_test {
	public static function setUpBeforeClass() {
		tpl()->COMPILE_TEMPLATES = true;
	}
	public static function tearDownAfterClass() {
		tpl()->COMPILE_TEMPLATES = false;
		_class('dir')->delete_dir('./stpls_compiled/', $delete_start_dir = true);
	}
}
