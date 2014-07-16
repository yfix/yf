<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_form_validate_test extends PHPUnit_Framework_TestCase {
	function test_complex() {
/*
		form($a)->text('name');
		$this->assertEquals('', common()->_get_error_messages());

		$old = $_SERVER['REQUEST_METHOD'];
		$_SERVER['REQUEST_METHOD'] = 'POST';
		form($a)->text('name', array('validate' => 'required'))->validate(array('name' => 'required'), array('name' => ''));
		$this->assertEquals('', common()->_get_error_messages());
		$_SERVER['REQUEST_METHOD'] = $old;
*/
	}
}