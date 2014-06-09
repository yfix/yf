<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

/*
// TODO:
_rewrite_replace_links()
_correct_protocol()
_get_unique_links()
*/

class class_rewrite_test extends PHPUnit_Framework_TestCase {
	private static $host = 'test.dev';
	private static $_bak_settings = array();

	public static function setUpBeforeClass() {
		self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$_SERVER['HTTP_HOST'] = self::$host;
	}
	public static function tearDownAfterClass() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
	}
	public function test_rewrite_enabled() {
		$this->assertEquals('http://'.self::$host.'/', module('rewrite')->_force_get_url() );
		$this->assertEquals('http://'.self::$host.'/', module('rewrite')->_force_get_url('') );
		$this->assertEquals('http://'.self::$host.'/', module('rewrite')->_force_get_url('', '') );
		$this->assertEquals('http://'.self::$host.'/', module('rewrite')->_force_get_url('', self::$host) );
	}
	public function test_rewrite_compatibility() {
		$this->assertEquals('http://'.self::$host.'/test', module('rewrite')->_force_get_url(array('object' => 'test'), self::$host) );
		$this->assertEquals('http://'.self::$host.'/test', _force_get_url(array('object' => 'test'), self::$host) );
		$this->assertEquals('http://'.self::$host.'/test', url(array('object' => 'test'), self::$host) );
	}
	public function test_rewrite_params_array() {
		$this->assertEquals('http://'.self::$host.'/test', url(array('object' => 'test', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test', url(array('object' => 'test', 'action' => 'show', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my', url(array('object' => 'test', 'action' => 'my', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my/123', url(array('object' => 'test', 'action' => 'my', 'id' => '123', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/123', url(array('object' => 'test', 'action' => 'show', 'id' => '123', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url(array('object' => 'test', 'action' => 'my', 'id' => '123', 'page' => '456', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/0/678', url(array('object' => 'test', 'page' => '678', 'host' => self::$host)) );
	}
	public function test_rewrite_task() {
		$this->assertEquals('http://'.self::$host.'/login', url(array('task' => 'login', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/logout', url(array('task' => 'logout', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/login/asdf', url(array('task' => 'login', 'id' => 'asdf', 'host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/?task=abcd', url(array('task' => 'abcd'), self::$host) );
	}
	public function test_rewrite_short_form() {
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456', array('host' => self::$host)) );

		$this->assertEquals('http://'.self::$host.'/test', url('/test', array('host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my', url('/test/my', array('host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my/123', url('/test/my/123', array('host' => self::$host)) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456', array('host' => self::$host)) );

		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test') );
		$this->assertEquals('http://'.self::$host.'/test/my', url(self::$host.'/test/my') );
		$this->assertEquals('http://'.self::$host.'/test/my/123', url(self::$host.'/test/my/123') );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url(self::$host.'/test/my/123/456') );

		$this->assertEquals('http://'.self::$host.'/test/123/456', url(self::$host.'/test//123/456') );
		$this->assertEquals('http://'.self::$host.'/test/123', url(self::$host.'/test//123') );
		$this->assertEquals('http://'.self::$host.'/test/0/456', url(self::$host.'/test///456') );

		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test') );
		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test/') );
		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test//') );
		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test///') );
		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test/////////////') );
		$this->assertEquals('http://'.self::$host.'/test', url(self::$host.'/test/////////////something') );
	}
	public function test_rewrite_enabled_simple() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://'.self::$host.'/test', process_url('./?object=test'));
	}
	public function test_rewrite_disabled_simple() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://'.self::$host.'/?object=test', process_url('./?object=test'));
	}
	public function test_rewrite_disabled() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://'.self::$host.'/', url() );
		$this->assertEquals('http://'.self::$host.'/', url('') );
		$this->assertEquals('http://'.self::$host.'/', url('/') );
		$this->assertEquals('http://'.self::$host.'/', url('/////') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url('/@object/testme/4') );
	}
	public function test_rewrite_url_user_enabled() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://'.self::$host.'/', url_user() );
		$this->assertEquals('http://'.self::$host.'/', url_user('') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/////') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('http://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('http://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
	}
	public function test_rewrite_url_user_disabled() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://'.self::$host.'/', url_user() );
		$this->assertEquals('http://'.self::$host.'/', url_user('') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/////') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
	}
	public function test_rewrite_url_admin() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals(ADMIN_WEB_PATH, url_admin() );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('/') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('/////') );
		$_GET['object'] = 'testobj';
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4', url_admin('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4', url_admin('/@object/testme/4') );
	}
}
