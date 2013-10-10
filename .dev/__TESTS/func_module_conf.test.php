<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

$GLOBALS['PROJECT_CONF'] = array();

class func_module_conf_test extends PHPUnit_Framework_TestCase {

	public function test_2() {
		$GLOBALS['PROJECT_CONF']['test']['test'] = '55';
		$this->assertEquals(module_conf('test', 'test'), '55');
	}
	public function test_3() {
		$GLOBALS['PROJECT_CONF']['test']['test']['sub'] = 'sub';
		$this->assertEquals(module_conf('test', 'test::sub'), 'sub');
	}
	public function test_4() {
		module_conf('test', array(
			'key1'	=> 'val1',
			'key2'	=> 'val2',
			'key3'	=> 'val3',
		));
		$_conf_should_be = array(
			'test' => array(
				'key1'	=> 'val1',
				'key2'	=> 'val2',
				'key3'	=> 'val3',
			),
		);
	   	$this->assertEquals($GLOBALS['PROJECT_CONF'], $_conf_should_be);
	}
	public function test_5() {
		module_conf('test', array(
			'key1'			=> 'val1',
			'key2::sub1'	=> 'val21',
			'key2::sub2'	=> 'val22',
			'key2::sub3::ss1'	=> 'val231',
			'key2::sub3::ss2'	=> 'val232',
			'key2::sub4::ss1::sss1'	=> 'val2411',
			'key2::sub4::ss1::sss2'	=> 'val2412',
		));
		$_conf_should_be = array(
			'test' => array(
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
			),
		);
	   	$this->assertEquals($GLOBALS['PROJECT_CONF'], $_conf_should_be);
	}
}
