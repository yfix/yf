<?php

!defined('YF_PATH') && define('YF_PATH', '/home/www/yf/');
require_once YF_PATH.'.dev/tests/yf_unit_tests_setup.php';

class class_test_plugin_test extends yf_unit_tests {
	public function test1() {
		$this->assertTrue(true);
	}
}