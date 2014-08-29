<?php

require_once __DIR__.'/tpl__setup.php';

class tpl_driver_smarty_test extends tpl_abstract {
	public static $driver_bak = array();
	protected function setUp() {
		if (defined('HHVM_VERSION')) {
			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
			return ;
	   	}
	}
	public static function setUpBeforeClass() {
		self::$driver_bak = tpl()->DRIVER_NAME;
		tpl()->_set_default_driver('smarty');
		parent::setUpBeforeClass();
	}
	public static function tearDownAfterClass() {
		tpl()->_set_default_driver(self::$driver_bak);
		_class('dir')->delete_dir('./templates_c/', $delete_start_dir = true);
		parent::tearDownAfterClass();
	}
	public function test_simple() {
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_condition() {
		$this->assertEquals('GOOD', self::_tpl( '{if $key1 eq "val1"}GOOD{/if}', array('key1' => 'val1') ));
	}
	public function test_complex() {
		$data = array(
			'a_variable' => 'var_value',
			'navigation' => array(
				array(
					'href'		=> 'http://yfix.net/',
					'caption'	=> 'Yf website',
				),
				array(
					'href'		=> 'http://google.com/',
					'caption'	=> 'Google',
				),
			),
		);
		$tpl_string = 
'<!DOCTYPE html>
<html>
	<head><title>My Webpage</title></head>
	<body>
		<ul id="navigation">
{foreach $navigation as $item}
			<li><a href="{$item.href}">{$item.caption}</a></li>
{/foreach}
		</ul>
		<h1>My Webpage</h1>
		{$a_variable}
	</body>
</html>';

		$expected = 
'<!DOCTYPE html>
<html>
	<head><title>My Webpage</title></head>
	<body>
		<ul id="navigation">
			<li><a href="http://yfix.net/">Yf website</a></li>
			<li><a href="http://google.com/">Google</a></li>
		</ul>
		<h1>My Webpage</h1>
		var_value
	</body>
</html>';
		$this->assertEquals(trim($expected), self::_tpl($tpl_string, $data));
	}
}