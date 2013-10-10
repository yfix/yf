<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_debug_test extends PHPUnit_Framework_TestCase {

	public function test_22() {
		$GLOBALS['DEBUG']['test'] = '55';
		$this->assertEquals(debug('test'), '55');
	}
	public function test_23() {
		$GLOBALS['DEBUG']['test']['sub'] = 'sub1';
		$this->assertEquals(debug('test::sub'), 'sub1');
	}
	public function test_24() {
		debug(array(
			'key1'	=> 'val1',
			'key2'	=> 'val2',
			'key3'	=> 'val3',
		));
		$_conf_should_be = array(
			'key1'	=> 'val1',
			'key2'	=> 'val2',
			'key3'	=> 'val3',
		);
	   	$this->assertEquals($GLOBALS['DEBUG'], $_conf_should_be);
	}
	public function test_25() {
		debug(array(
			'key1'			=> 'val1',
			'key2::sub1'	=> 'val21',
			'key2::sub2'	=> 'val22',
			'key2::sub3::ss1'	=> 'val231',
			'key2::sub3::ss2'	=> 'val232',
			'key2::sub4::ss1::sss1'	=> 'val2411',
			'key2::sub4::ss1::sss2'	=> 'val2412',
		));
		$_conf_should_be = array(
			'key1'	=> 'val1',
			'key2'	=> array(
				'sub1'	=> 'val21',
				'sub2'	=> 'val22',
				'sub3'	=> array(
					'ss1'	=> 'val231',
					'ss2'	=> 'val232',
				),
				'sub4'	=> array(
					'ss1'	=> array(
						'sss1'	=> 'val2411',
						'sss2'	=> 'val2412',
					),
				),
			),
		);
	   	$this->assertEquals($GLOBALS['DEBUG'], $_conf_should_be);
	}
	public function test_26() {
		$GLOBALS['DEBUG'] = array(
			'key2'	=> array(
				'sub4'	=> array(
					'ss1'	=> array(
						'sss2'	=> 'val2412',
					),
				),
			),
		);
	   	$this->assertEquals(debug('key2::sub4::ss1::sss2'), 'val2412');
	}
}
