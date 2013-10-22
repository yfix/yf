<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_include_compiled_test extends tpl_abstract {
	public function test_110() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$result = self::_tpl( '{include("unittest_include1")}' );
		$this->assertEquals('Hello from include', $result);
	}
	public function test_111() {
		self::_tpl( 'Inherited var: {key1}', array(), 'unittest_include2' );
// TODO: we need to avoid this double execution
		// Needed 2 times to correctly compile templates
		$result = self::_tpl( '{include("unittest_include2")}', array('key1' => 'val1') );
		$result = self::_tpl( '{include("unittest_include2")}', array('key1' => 'val1') );
		$this->assertEquals('Inherited var: val1', $result);
	}
	public function test_112() {
		self::_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), 'unittest_include3' );
// TODO: we need to avoid this double execution
		// Needed 2 times to correctly compile templates
		$result = self::_tpl( '{include("unittest_include3",var2=42)}', array('key1' => 'val1') );
		$result = self::_tpl( '{include("unittest_include3",var2=42)}', array('key1' => 'val1') );
		$this->assertEquals('Inherited var: val1, passed var: 42', $result);
	}
	public function test_113() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$result = self::_tpl( '{include("unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' );
		$this->assertEquals('Included: v1 v2 v3 v4', $result);
	}
}
