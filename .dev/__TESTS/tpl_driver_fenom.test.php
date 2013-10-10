<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'fenom';
new yf_main('user', 1, 0);

function _tpl($stpl_text = '', $replace = array(), $name = '', $params = array()) {
	return tpl()->parse_string($stpl_text, $replace, $name, $params);
}

class tpl_driver_fenom_test extends PHPUnit_Framework_TestCase {
	public function test_10() {
		$this->assertEquals('Hello world', _tpl( 'Hello world' ));
	}
	public function test_60() {
		$this->assertEquals("GOOD", _tpl( '{if $key1 eq "val1"}GOOD{/if}', array("key1" => "val1") ));
	}
}