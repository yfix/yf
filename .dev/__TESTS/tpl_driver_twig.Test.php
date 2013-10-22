<?php

$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'twig';
require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_twig_test extends tpl_abstract {
	public static $driver_bak = array();
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$driver_bak = tpl()->DRIVER_NAME;
		tpl()->DRIVER_NAME = 'blitz';
	}
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		tpl()->DRIVER_NAME = self::$driver_bak;
	}
	public function test_10() {
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
	}
	public function test_60() {
		$result = tpl('
			<!DOCTYPE html>
			<html>
			    <head>
			        <title>My Webpage</title>
			    </head>
			    <body>
			        <ul id="navigation">
			        {% for item in navigation %}
			            <li><a href="{{ item.href }}">{{ item.caption }}</a></li>
			        {% endfor %}
			        </ul>
			        <h1>My Webpage</h1>
			        {{ a_variable }}
			    </body>
			</html>
		');
		$this->assertNotEmpty($result);
	}
}