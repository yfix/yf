<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_obj2arr extends yf\tests\wrapper {
	private function _get_test_obj() {
		$o = new stdClass();
		$o->key1 = 'val1';
		$o->key2 = 'val2';
		return $o;
	}
	public function test_obj2arr() {
		$a = [
			'key1' => 'val1',
			'key2' => 'val2',
		];
		$o = $this->_get_test_obj();
		$this->assertEquals($a, obj2arr($o));
		$this->assertEquals($a, $o);
		$this->assertNotEquals($a + ['key3' => 'val3'], obj2arr($o));
		$o = $this->_get_test_obj();
		$o->key3 = 'val3';
		$this->assertEquals($a + ['key3' => 'val3'], obj2arr($o));
		$this->assertEquals($a + ['key3' => 'val3'], $o);
		$o = $this->_get_test_obj();
		$o->key4 = ['v4','v44','v444'];
		$this->assertEquals($a + ['key4' => ['v4','v44','v444']], obj2arr($o));
	}
	public function test_object_to_array() {
		$a = [
			'key1' => 'val1',
			'key2' => 'val2',
		];
		$o = $this->_get_test_obj();
		$this->assertEquals($a, object_to_array($o));
		$this->assertNotEquals($a, $o);
		$this->assertNotEquals($a + ['key3' => 'val3'], object_to_array($o));
		$o = $this->_get_test_obj();
		$o->key3 = 'val3';
		$this->assertEquals($a + ['key3' => 'val3'], object_to_array($o));
		$this->assertNotEquals($a + ['key3' => 'val3'], $o);
		$o = $this->_get_test_obj();
		$o->key4 = ['v4','v44','v444'];
		$this->assertEquals($a + ['key4' => ['v4','v44','v444']], object_to_array($o));
	}
	public function test_array_to_object() {
		$a = [
			'key1' => 'val1',
			'key2' => 'val2',
		];
		$o = $this->_get_test_obj();
		$this->assertEquals($o, array_to_object($a));
		$this->assertNotEquals($a, $o);
		$o->key3 = 'val3';
		$this->assertNotEquals($a + ['key3' => 'val3'], array_to_object($o));
	}
}