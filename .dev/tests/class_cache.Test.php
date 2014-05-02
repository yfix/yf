<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_cache_test extends PHPUnit_Framework_TestCase {
	public static $_er = array();
#	public static function setUpBeforeClass() {
#		self::$_er = error_reporting();
#		error_reporting(0);
#	}
#	public static function tearDownAfterClass() {
#		error_reporting(self::$_er);
#	}
	public function test_set() {
#		$this->assertEquals('', cache()->get());
	}
	public function test_get() {
#		$this->assertEquals('', cache()->set());
	}
	public function test_del() {
#		$this->assertEquals('', cache()->del());
	}
	public function test_flush() {
#		$this->assertEquals('', cache()->flush());
	}
	public function test_list_keys() {
#		$this->assertEquals('', cache()->list_keys());
	}
	public function test_multi_get() {
#		$this->assertEquals('', cache()->multi_get());
	}
	public function test_multi_set() {
#		$this->assertEquals('', cache()->multi_set());
	}
	public function test_multi_del() {
#		$this->assertEquals('', cache()->multi_del());
	}
	public function test_del_by_prefix() {
#		$this->assertEquals('', cache()->del_by_prefix());
	}
}
