<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_attrs_string2array_test extends PHPUnit_Framework_TestCase {
	public function test_negative() {
		$this->assertEquals(array(), _attrs_string2array());
		$this->assertEquals(array(), _attrs_string2array(null));
		$this->assertEquals(array(), _attrs_string2array(''));
#		$this->assertEquals(array(), _attrs_string2array(array()));
#		$this->assertEquals(array(), _attrs_string2array(new stdClass()));
#		$this->assertEquals(array(), _attrs_string2array(function(){}));
	}
	public function test_simple() {
		$a = array('k1' => 'v1');
		$this->assertEquals($a, _attrs_string2array('k1=v1'));
		$this->assertEquals($a, _attrs_string2array(' k1=v1'));
		$this->assertEquals($a, _attrs_string2array('k1=v1 '));
		$this->assertEquals($a, _attrs_string2array(' k1=v1 '));
		$this->assertEquals($a, _attrs_string2array(' k1 = v1 '));
		$this->assertEquals($a, _attrs_string2array(' k1 = v1; '));
		$this->assertEquals($a, _attrs_string2array('   ;,k1   =   v1,;   '));
	}
	public function test_two() {
		$a = array('k1' => 'v1', 'k2' => 'v2');
		$this->assertEquals($a, _attrs_string2array('k1=v1;k2=v2'));
		$this->assertEquals($a, _attrs_string2array(' k1 = v1 ; k2 = v2 '));
		$this->assertEquals($a, _attrs_string2array('  k1  =  v1  ,  k2  =  v2  '));
	}
}