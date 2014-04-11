<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_blitz_test extends tpl_abstract {
	public static $_bak = array();
	public static function setUpBeforeClass() {
		self::$_bak = tpl()->DRIVER_NAME;
		tpl()->DRIVER_NAME = 'blitz';
// TODO: testme if this enough
		self::$ok = class_exists('Blitz');
	}
	public static function tearDownAfterClass() {
		tpl()->DRIVER_NAME = self::$_bak;
	}
	public function test_10() {
		if (!self::$ok) {
			return false;
		}
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_60() {
		if (!self::$ok) {
			return false;
		}
		$this->assertEquals('Test var: value1', self::_tpl( 'Test var: {{ $var1 }}', array('var1' => 'value1') ));
	}
}