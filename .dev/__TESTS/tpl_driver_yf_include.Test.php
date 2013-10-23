<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_include_test extends tpl_abstract {
	public function test_110() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include("unittest_include1")}' ));
	}
	public function test_111() {
		self::_tpl( 'Inherited var: {key1}', array(), 'unittest_include2' );
		$this->assertEquals('Inherited var: val1', self::_tpl( '{include("unittest_include2")}', array('key1' => 'val1') ));
	}
	public function test_112() {
		self::_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), 'unittest_include3' );
		$this->assertEquals('Inherited var: val1, passed var: 42', self::_tpl( '{include("unittest_include3",var2=42)}', array('key1' => 'val1') ));
	}
	public function test_113() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include("unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
	}
	public function test_114() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
	}
	public function test_115() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" ,var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
	}
	public function test_116_1() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( unittest_include4 , var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
	}
	public function test_116_2() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( unittest_include4, var1=v1;var2=v2;var3=v3;var4=v4 )}' ));
	}
	public function test_116_3() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" , var1=v1 ;var2=v2 ;var3=v3 ;var4=v4 )}' ));
	}
	public function test_116_4() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" , var1=v1; var2=v2; var3=v3; var4=v4 )}' ));
	}
	public function test_116_5() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( "unittest_include4" , var1=v1 ; var2=v2 ; var3=v3 ; var4=v4 )}' ));
	}
	public function test_116_6() {
		self::_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include4' );
		$this->assertEquals('Included: v1 v2 v3 v4', self::_tpl( '{include( unittest_include4 , var1 = v1 ; var2 = v2 ; var3 = v3 ; var4 = v4 )}' ));
	}
	public function test_117() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include( "unittest_include1")}' ));
	}
	public function test_118() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include("unittest_include1" )}' ));
	}
	public function test_119() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include( "unittest_include1" )}' ));
	}
	public function test_120() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include( unittest_include1)}' ));
	}
	public function test_121() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include(unittest_include1 )}' ));
	}
	public function test_122() {
		self::_tpl( 'Hello from include', array(), 'unittest_include1' );
		$this->assertEquals('Hello from include', self::_tpl( '{include( unittest_include1 )}' ));
	}
}