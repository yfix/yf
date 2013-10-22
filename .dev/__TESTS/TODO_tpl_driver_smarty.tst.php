<?php

$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'smarty';
require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_smarty_test extends tpl_abstract {
	public function test_10() {
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_60() {
		$this->assertEquals('GOOD', self::_tpl( '{if $key1 eq "val1"}GOOD{/if}', array('key1' => 'val1') ));
	}
	public function tearDown() {
		parent::tearDown();
		_class('dir')->delete_dir('./templates_c/', $delete_start_dir = true);
	}
}