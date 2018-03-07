<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_conf_test extends yf\tests\wrapper {
	public static $_bak = [];
	public static function setUpBeforeClass() {
		self::$_bak = $GLOBALS['CONF'];
		$GLOBALS['CONF'] = [];
	}
	public static function tearDownAfterClass() {
		$GLOBALS['CONF'] = self::$_bak;
	}
	protected function setUp() {
		$GLOBALS['CONF'] = [];
	}
	public function test_12() {
		$name = __FUNCTION__;
		$GLOBALS['CONF'][$name] = '55';
		$this->assertEquals(conf($name), '55');
	}
	public function test_13() {
		$name = __FUNCTION__;
		$GLOBALS['CONF'][$name]['sub'] = 'sub1';
		$this->assertEquals(conf($name.'::sub'), 'sub1');
	}
	public function test_14() {
		conf([
			'key1'	=> 'val1',
			'key2'	=> 'val2',
			'key3'	=> 'val3',
		]);
		$_conf_should_be = [
			'key1'	=> 'val1',
			'key2'	=> 'val2',
			'key3'	=> 'val3',
		];
	   	$this->assertEquals($GLOBALS['CONF'], $_conf_should_be);
	}
	public function test_15() {
		conf([
			'key1'			=> 'val1',
			'key2::sub1'	=> 'val21',
			'key2::sub2'	=> 'val22',
			'key2::sub3::ss1'	=> 'val231',
			'key2::sub3::ss2'	=> 'val232',
			'key2::sub4::ss1::sss1'	=> 'val2411',
			'key2::sub4::ss1::sss2'	=> 'val2412',
		]);
		$_conf_should_be = [
			'key1'	=> 'val1',
			'key2'	=> [
				'sub1'	=> 'val21',
				'sub2'	=> 'val22',
				'sub3'	=> [
					'ss1'	=> 'val231',
					'ss2'	=> 'val232',
				],
				'sub4'	=> [
					'ss1'	=> [
						'sss1'	=> 'val2411',
						'sss2'	=> 'val2412',
					],
				],
			],
		];
	   	$this->assertEquals($GLOBALS['CONF'], $_conf_should_be);
	}
	public function test_16() {
		$GLOBALS['CONF'] = [
			'key2'	=> [
				'sub4'	=> [
					'ss1'	=> [
						'sss2'	=> 'val2412',
					],
				],
			],
		];
	   	$this->assertEquals(conf('key2::sub4::ss1::sss2'), 'val2412');
	}
}
