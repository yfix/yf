<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_rewrite_test extends PHPUnit_Framework_TestCase {
	private static $host = 'test.dev';
	private static $_bak_settings = array();

	public static function setUpBeforeClass() {
		self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
	}

	public static function tearDownAfterClass() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
	}

	public function test_required_1() {
#		$this->assertEquals(false, module('rewrite')->_force_get_url() );
#		$this->assertEquals(false, module('rewrite')->_force_get_url('') );
#		$this->assertEquals(false, module('rewrite')->_force_get_url('', '') );
#		$this->assertEquals(false, module('rewrite')->_force_get_url('', self::$host) );

		$this->assertEquals('http://'.self::$host.'/test', _force_get_url(array('object' => 'test'), self::$host) );
		$this->assertEquals('http://'.self::$host.'/test', _force_get_url(array('object' => 'test', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test', _force_get_url(array('object' => 'test', 'action' => 'show', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my', _force_get_url(array('object' => 'test', 'action' => 'my', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my/123', _force_get_url(array('object' => 'test', 'action' => 'my', 'id' => '123', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/123', _force_get_url(array('object' => 'test', 'action' => 'show', 'id' => '123', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', _force_get_url(array('object' => 'test', 'action' => 'my', 'id' => '123', 'page' => '456', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/0/678', _force_get_url(array('object' => 'test', 'page' => '678', 'host' => self::$host)) );

		$this->assertEquals('http://'.self::$host.'/login', _force_get_url(array('task' => 'login', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/logout', _force_get_url(array('task' => 'logout', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/login/asdf', _force_get_url(array('task' => 'login', 'id' => 'asdf', 'host' => self::$host)) );

#		$this->assertEquals('http://'.self::$host.'/', _force_get_url(array('task' => 'fdsfdsfds'), self::$host) );

		// Short form of writing _force_get_url
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', _force_get_url('/test/my/123/456', array('host' => self::$host)) );

		// Alias
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456', array('host' => self::$host)) );
	}

/*
// TODO:
_rewrite_replace_links()
_correct_protocol()
_get_unique_links()
*/

}