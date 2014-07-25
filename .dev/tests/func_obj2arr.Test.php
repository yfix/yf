<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_obj2arr extends PHPUnit_Framework_TestCase {
	private function _get_test_obj() {
		$o = new stdClass();
		$o->key1 = 'val1';
		$o->key2 = 'val2';
		return $o;
	}
	public function test_obj2arr() {
		$a = array(
			'key1' => 'val1',
			'key2' => 'val2',
		);
		$o = $this->_get_test_obj();
		$this->assertEquals($a, obj2arr($o));
		$this->assertEquals($a, $o);
		$this->assertNotEquals($a + array('key3' => 'val3'), obj2arr($o));
		$o = $this->_get_test_obj();
		$o->key3 = 'val3';
		$this->assertEquals($a + array('key3' => 'val3'), obj2arr($o));
		$this->assertEquals($a + array('key3' => 'val3'), $o);
		$o = $this->_get_test_obj();
		$o->key4 = array('v4','v44','v444');
		$this->assertEquals($a + array('key4' => array('v4','v44','v444')), obj2arr($o));
	}
	public function test_object_to_array() {
		$a = array(
			'key1' => 'val1',
			'key2' => 'val2',
		);
		$o = $this->_get_test_obj();
		$this->assertEquals($a, object_to_array($o));
		$this->assertNotEquals($a, $o);
		$this->assertNotEquals($a + array('key3' => 'val3'), object_to_array($o));
		$o = $this->_get_test_obj();
		$o->key3 = 'val3';
		$this->assertEquals($a + array('key3' => 'val3'), object_to_array($o));
		$this->assertNotEquals($a + array('key3' => 'val3'), $o);
		$o = $this->_get_test_obj();
		$o->key4 = array('v4','v44','v444');
		$this->assertEquals($a + array('key4' => array('v4','v44','v444')), object_to_array($o));
	}
	public function test_array_to_object() {
		$a = array(
			'key1' => 'val1',
			'key2' => 'val2',
		);
		$o = $this->_get_test_obj();
		$this->assertEquals($o, array_to_object($a));
		$this->assertNotEquals($a, $o);
		$o->key3 = 'val3';
		$this->assertNotEquals($a + array('key3' => 'val3'), array_to_object($o));
	}
}