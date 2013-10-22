<?php

$GLOBALS['PROJECT_CONF']['tpl']['DRIVER_NAME'] = 'twig';
require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_twig_test extends PHPUnit_Framework_TestCase {
	public function test_10() {
		$this->assertEquals('Hello world', _tpl( 'Hello world' ));
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