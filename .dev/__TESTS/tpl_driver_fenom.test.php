<?php

$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'fenom';
require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_fenom_test extends PHPUnit_Framework_TestCase {
	public function test_10() {
		$this->assertEquals('Hello world', _tpl( 'Hello world' ));
	}
	public function test_60() {
		$result = _tpl( 
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
		$this->assertNotEmpty('GOOD', $result);
	}
}