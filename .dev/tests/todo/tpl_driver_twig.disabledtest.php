<?php

require_once __DIR__.'/tpl__setup.php';

class tpl_driver_twig_test extends tpl_abstract {
	public static $driver_bak = array();
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$driver_bak = tpl()->DRIVER_NAME;
		tpl()->_set_default_driver('twig');
	}
	public static function tearDownAfterClass() {
		tpl()->_set_default_driver(self::$driver_bak);
		parent::tearDownAfterClass();
	}
	public function test_ensure_driver() {
		$this->assertEquals('twig', tpl()->DRIVER_NAME);
	}
	public function test_simple() {
		$this->assertEquals('Hello world', self::_tpl( 'Hello world' ));
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
{% for item in navigation %}
			<li><a href="{{ item.href }}">{{ item.caption }}</a></li>
{% endfor %}
		</ul>
		<h1>My Webpage</h1>
		{{ a_variable }}
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
		$this->assertEquals($expected, self::_tpl($tpl_string, $data));
	}
}