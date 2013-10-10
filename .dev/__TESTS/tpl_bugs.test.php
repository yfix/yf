<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
#$GLOBALS['PROJECT_CONF']['tpl']['COMPILE_TEMPLATES'] = true;
new yf_main('user', 1, 0);

function _tpl($stpl_text = '', $replace = array(), $name = '', $params = array()) {
	return tpl()->parse_string($stpl_text, $replace, $name, $params);
}

class tpl_core_test extends PHPUnit_Framework_TestCase {
	public function test_bug_01() {
		$this->assertEquals('#description ', _tpl( '#description {execute(main,_show_block123123)}', array('description' => 'test') ));
	}
	public function test_bug_02_1() {
		_tpl( 'Hello from include', array(), 'unittest_include' );
		$this->assertEquals('Hello from include', _tpl( '{include("unittest_include")}' ));
	}
	public function test_bug_02_2() {
		_tpl( 'Inherited var: {key1}', array(), 'unittest_include' );
		$this->assertEquals('Inherited var: val1', _tpl( '{include("unittest_include")}', array('key1' => 'val1') ));
	}
	public function test_bug_02_3() {
		_tpl( 'Inherited var: {key1}, passed var: {var2}', array(), 'unittest_include' );
		$this->assertEquals('Inherited var: val1, passed var: 42', _tpl( '{include("unittest_include",var2=42)}', array('key1' => 'val1') ));
	}
	public function test_bug_02_4() {
		_tpl( 'Included: {var1} {var2} {var3} {var4}', array(), 'unittest_include' );
		$this->assertEquals('Included: v1 v2 v3 v4', _tpl( '{include("unittest_include",var1=v1;var2=v2;var3=v3;var4=v4)}' ));
	}
}