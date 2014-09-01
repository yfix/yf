<?php

require_once __DIR__.'/tpl__setup.php';

/**
 * @requires extension blitz
 */
class tpl_driver_blitz_test extends tpl_abstract {
	public static $_bak = array();
	public static function setUpBeforeClass() {
		self::$_bak = tpl()->DRIVER_NAME;
		tpl()->DRIVER_NAME = 'blitz';
	}
	public static function tearDownAfterClass() {
		tpl()->DRIVER_NAME = self::$_bak;
	}
	public function test_10() {
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_60() {
		$this->assertEquals('Test var: value1', self::_tpl( 'Test var: {{ $var1 }}', array('var1' => 'value1') ));
	}
}