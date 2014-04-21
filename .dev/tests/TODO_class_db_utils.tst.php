<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';
require dirname(__FILE__).'/db_setup.php';

class class_db_utils_test extends PHPUnit_Framework_TestCase {
	public static $_er = array();
	public static function setUpBeforeClass() {
		self::$_er = error_reporting();
		error_reporting(0);
	}
	public static function tearDownAfterClass() {
		error_reporting(self::$_er);
	}
}