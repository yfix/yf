<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_include_test extends PHPUnit_Framework_TestCase {
	public function test_110() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include("unittest_include1")}' ));
	}
	public function test_111() {
		_tpl( 'Inherited var: {key1}', array(), 'unittest_include2' );
		$this->assertEquals('Inherited var: val1', _tpl( '{include("unittest_include2")}', array('key1' => 'val1') ));
	}
	public function test_112() {
		_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), 'unittest_include3' );
		$this->assertEquals('Inherited var: val1, passed var: 42', _tpl( '{include("unittest_include3",var2=42)}', array('key1' => 'val1') ));
	}
	public function test_113() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include("unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
	}
	public function test_114() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( "unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
	}
	public function test_115() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( "unittest_include4" ,var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
	}
	public function test_116_1() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( unittest_include4 , var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
	}
	public function test_116_2() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( unittest_include4, var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
	}
	public function test_116_3() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( "unittest_include4" , var1=v1 ;var2=v2 ;var3=v3 ;var4=v4 )}' ));
	}
	public function test_116_4() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( "unittest_include4" , var1=v1; var2=v2; var3=v3; var4=v4 )}' ));
	}
	public function test_116_5() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( "unittest_include4" , var1=v1 ; var2=v2 ; var3=v3 ; var4=v4 )}' ));
	}
	public function test_116_6() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include( unittest_include4 , var1 = v1 ; var2 = v2 ; var3 = v3 ; var4 = v4 )}' ));
	}
	public function test_117() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include( "unittest_include1")}' ));
	}
	public function test_118() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include("unittest_include1" )}' ));
	}
	public function test_119() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include( "unittest_include1" )}' ));
	}
	public function test_120() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include( unittest_include1)}' ));
	}
	public function test_121() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include(unittest_include1 )}' ));
	}
	public function test_122() {
		_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', _tpl( '{include( unittest_include1 )}' ));
	}
}