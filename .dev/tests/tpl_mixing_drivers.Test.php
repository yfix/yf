<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_mixing_drivers_test extends tpl_abstract {
	public static function tearDownAfterClass() {
		_class('dir')->delete_dir('./templates_c/', $delete_start_dir = true);
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
}