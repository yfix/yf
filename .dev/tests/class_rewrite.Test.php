<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

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
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_force_get_url() );
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_force_get_url('') );
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_force_get_url('', '') );
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_force_get_url('', self::$host) );
	}
	public function test_rewrite_compatibility() {
		$this->assertEquals('http://'.self::$host.'/test', _class('rewrite')->_force_get_url(array('object' => 'test'), self::$host) );
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
	public function test_get_unique_links() {
		$html = '<a href="http://google.com/">
			<a href="./?object=obj&action=act">
			<form action="./?object=form&action=method">
			<a href="./?object=obj&action=act&id=1&page=1">';
		$links = array(
			'./?object=obj&action=act',
			'./?object=form&action=method',
			'./?object=obj&action=act&id=1&page=1',
		);
		$this->assertEquals($links, _class('rewrite')->_get_unique_links($html) );
	}
	public function test_rewrite_replace_links() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;

		$in = '<body><a href="http://google.com/">
			<a href="./?object=obj&action=act">
			<form action="./?object=form&action=method">
			<a href="./?object=obj&action=act&id=1&page=1">
			</body>';
		$out = '<body><a href="http://google.com/">
			<a href="http://test.dev/obj/act">
			<form action="http://test.dev/form/method">
			<a href="http://test.dev/obj/act/1/1">
			</body>';
		$this->assertEquals($out, _class('rewrite')->_rewrite_replace_links($in) );

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$out2 = '<body><a href="http://google.com/">
			<a href="http://test.dev/?object=obj&action=act">
			<form action="http://test.dev/?object=form&action=method">
			<a href="http://test.dev/?object=obj&action=act&id=1&page=1">
			</body>';
		$this->assertEquals($out2, _class('rewrite')->_rewrite_replace_links($in) );
	}
	public function test_correct_protocol() {
		$old = main()->USE_ONLY_HTTPS;
		main()->USE_ONLY_HTTPS = true;

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );

		main()->USE_ONLY_HTTPS = false;

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );

		main()->USE_ONLY_HTTPS = $old;
	}
	public function test_https_only() {
		$old = main()->USE_ONLY_HTTPS;
		main()->USE_ONLY_HTTPS = true;

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('https://'.self::$host.'/', url_user() );
		$this->assertEquals('https://'.self::$host.'/', url_user('') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/////') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('https://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('https://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('https://'.self::$host.'/', url_user() );
		$this->assertEquals('https://'.self::$host.'/', url_user('') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/////') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('https://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('https://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );

		main()->USE_ONLY_HTTPS = $old;
	}
	public function test_https_enabled_for() {
		$old1 = main()->HTTPS_ENABLED_FOR;
		$old2 = main()->USE_ONLY_HTTPS;

		main()->USE_ONLY_HTTPS = false;
		main()->HTTPS_ENABLED_FOR = array(
			'object=sslme&action=hello',
			'object=other',
			'/sslme/hello',
			'/other',
		);
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://'.self::$host.'/?object=sslme', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=sslme') );
		$this->assertEquals('http://'.self::$host.'/?object=sslme', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=sslme&action=hello') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme&action=hello') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=sslme&action=hello&id=1&page=2') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2') );
		$this->assertEquals('http://'.self::$host.'/?object=not_https', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=not_https') );
		$this->assertEquals('http://'.self::$host.'/?object=not_https', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=not_https') );
		$this->assertEquals('http://'.self::$host.'/?object=not_https&action=hello', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=not_https&action=hello') );
		$this->assertEquals('http://'.self::$host.'/?object=not_https&action=hello', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=not_https&action=hello') );

		$this->assertEquals('http://'.self::$host.'/sslme', _class('rewrite')->_correct_protocol('http://'.self::$host.'/sslme') );
		$this->assertEquals('https://'.self::$host.'/sslme/hello', _class('rewrite')->_correct_protocol('http://'.self::$host.'/sslme/hello') );
		$this->assertEquals('https://'.self::$host.'/sslme/hello/1', _class('rewrite')->_correct_protocol('http://'.self::$host.'/sslme/hello/1') );
		$this->assertEquals('https://'.self::$host.'/sslme/hello/1/2', _class('rewrite')->_correct_protocol('http://'.self::$host.'/sslme/hello/1/2') );
		$this->assertEquals('http://'.self::$host.'/not_https', _class('rewrite')->_correct_protocol('http://'.self::$host.'/not_https') );
		$this->assertEquals('http://'.self::$host.'/not_https/hello', _class('rewrite')->_correct_protocol('http://'.self::$host.'/not_https/hello') );

		$this->assertEquals('http://'.self::$host.'/sslme', process_url('./?object=sslme') );
		$this->assertEquals('https://'.self::$host.'/sslme/hello', process_url('./?object=sslme&action=hello') );
		$this->assertEquals('https://'.self::$host.'/sslme/hello/1', process_url('./?object=sslme&action=hello&id=1') );
		$this->assertEquals('https://'.self::$host.'/sslme/hello/1/2', process_url('./?object=sslme&action=hello&id=1&page=2') );
		$this->assertEquals('http://'.self::$host.'/not_https', process_url('./?object=not_https') );
		$this->assertEquals('http://'.self::$host.'/not_https/hello', process_url('./?object=not_https&action=hello') );

		main()->HTTPS_ENABLED_FOR = $old1;
		main()->USE_ONLY_HTTPS = $old2;
	}
}
