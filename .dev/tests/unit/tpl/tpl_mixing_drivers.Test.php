<?php

require_once __DIR__.'/tpl__setup.php';

class tpl_mixing_drivers_test extends tpl_abstract {
#	protected function setUp() {
#		if (defined('HHVM_VERSION')) {
#			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
#			return ;
#	   	}
#	}
	public static function tearDownAfterClass() {
		_class('dir')->delete_dir(STORAGE_PATH.'templates_c/', $delete_start_dir = true);
		parent::tearDownAfterClass();
	}
	public function test_main() {
		$data = array(
			'var1' => 'var_value1',
		);
		$expected = 'Hello world from driver, var1: var_value1';
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver, var1: {var1}', $data, $name = 'test1', array('driver' => 'yf') ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver, var1: {$var1}', $data, $name = 'test1', array('driver' => 'smarty') ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver, var1: {$var1}', $data, $name = 'test1', array('driver' => 'fenom') ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver, var1: {{ var1 }}', $data, $name = 'test1', array('driver' => 'twig') ));
	}
	public function test_by_name() {
		$data = array(
			'var1' => 'var_value1',
		);
		$expected = 'Hello world from driver by name, var1: var_value1';
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver by name, var1: {var1}', $data, 'test2' ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver by name, var1: {var1}', $data, 'yf:test2' ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver by name, var1: {$var1}', $data, 'smarty:test2' ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver by name, var1: {$var1}', $data, 'fenom:test2' ));
		$this->assertEquals($expected, self::_tpl( 'Hello world from driver by name, var1: {{ var1 }}', $data, 'twig:test2' ));
	}
}