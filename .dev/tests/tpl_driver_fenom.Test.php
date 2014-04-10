<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_fenom_test extends tpl_abstract {
	public static $driver_bak = array();
	public static function setUpBeforeClass() {
		self::$driver_bak = tpl()->DRIVER_NAME;
		tpl()->DRIVER_NAME = 'fenom';
		parent::setUpBeforeClass();
	}
	public static function tearDownAfterClass() {
		tpl()->DRIVER_NAME = self::$driver_bak;
		_class('dir')->delete_dir('./templates_c/', $delete_start_dir = true);
		parent::tearDownAfterClass();
	}
	public function test_10() {
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_60() {
		$result = self::_tpl( 
			'<html>
			    <head>
			        <title>Fenom</title>
			    </head>
			    <body>
				    {if $templaters.fenom?}
				        {var $tpl = $templaters.fenom}
				        <div>Name: {$tpl.name}</div>
				        <div>Description: {$tpl.name|truncate:80}</div>
			    	    <ul>
			        	{foreach $tpl.features as $feature}
				            <li>{$feature.name} (from {$feature.timestamp|gmdate:"Y-m-d H:i:s"})</li>
				        {/foreach}
				        </ul>
				    {/if}
			    </body>
			</html>'
		);
		$this->assertNotEmpty($result);
	}
}