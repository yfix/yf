<?php

#$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'smarty';
require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_smarty_test extends tpl_abstract {
	public static $driver_bak = array();
	public static function setUpBeforeClass() {
#		$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'smarty';
		self::$driver_bak = tpl()->DRIVER_NAME;
		tpl()->DRIVER_NAME = 'smarty';
		parent::setUpBeforeClass();
	}
	public static function tearDownAfterClass() {
		tpl()->DRIVER_NAME = self::$driver_bak;
		_class('dir')->delete_dir('./templates_c/', $delete_start_dir = true);
		parent::tearDownAfterClass();
	}
	public function test_10() {
#echo tpl()->DRIVER_NAME;
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_60() {
		$this->assertEquals('GOOD', self::_tpl( '{if $key1 eq "val1"}GOOD{/if}', array('key1' => 'val1') ));
	}
}