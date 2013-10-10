<?php

$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'blitz';
require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_blitz_test extends PHPUnit_Framework_TestCase {
	public function test_10() {
#		$this->assertEquals('Hello world', _tpl( 'Hello world' ));
	}
	public function test_60() {
#		$this->assertEquals('GOOD', _tpl( '{if $key1 eq "val1"}GOOD{/if}', array('key1' => 'val1') ));
	}
}