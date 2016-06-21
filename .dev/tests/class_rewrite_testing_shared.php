<?php

class class_rewrite_testing_shared_test extends yf_unit_tests {
	private static $host = 'test.dev';
	private static $_bak_settings = [];

	public static function setUpBeforeClass() {
		self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$_GET = [
			'object' => 'dynamic',
			'action' => 'unit_test_form',
		];
		$_SERVER['HTTP_HOST'] = self::$host;
		_class('rewrite')->DEFAULT_HOST = self::$host;
	}
	public static function tearDownAfterClass() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
	}
	public function test_rewrite_enabled() {
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_url() );
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_url('') );
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_url('', '') );
		$this->assertEquals('http://'.self::$host.'/', _class('rewrite')->_url('', self::$host) );
	}
	public function test_rewrite_compatibility() {
		$this->assertEquals('http://'.self::$host.'/test', _class('rewrite')->_url(['object' => 'test'], self::$host) );
		$this->assertEquals('http://'.self::$host.'/test', url(['object' => 'test'], self::$host) );
	}
	public function test_rewrite_params_array() {
		$this->assertEquals('http://'.self::$host.'/test', url(['object' => 'test', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test', url(['object' => 'test', 'action' => 'show', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/my', url(['object' => 'test', 'action' => 'my', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/my/123', url(['object' => 'test', 'action' => 'my', 'id' => '123', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/123', url(['object' => 'test', 'action' => 'show', 'id' => '123', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url(['object' => 'test', 'action' => 'my', 'id' => '123', 'page' => '456', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/0/678', url(['object' => 'test', 'page' => '678', 'host' => self::$host]) );
	}
	public function test_rewrite_task() {
		$this->assertEquals('http://'.self::$host.'/login', url(['task' => 'login', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/logout', url(['task' => 'logout', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/login/asdf', url(['task' => 'login', 'id' => 'asdf', 'host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?task=abcd', url(['task' => 'abcd'], self::$host) );
	}
	public function test_rewrite_short_form() {
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456', ['host' => self::$host]) );

		$this->assertEquals('http://'.self::$host.'/test', url('/test', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/my', url('/test/my', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/my/123', url('/test/my/123', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456', ['host' => self::$host]) );

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

		unset($_GET['debug']);
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456?host='.self::$host) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456', url('/test/my/123/456/?host='.self::$host) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1', url('/test/my/123/456', ['k1' => 'v1']) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1&k2=v2', url('/test/my/123/456?k2=v2', ['k1' => 'v1']) );
		$_GET['debug'] = '555';
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1&debug='.$_GET['debug'], url('/test/my/123/456', ['k1' => 'v1']) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1&k2=v2&debug='.$_GET['debug'], url('/test/my/123/456?k2=v2', ['k1' => 'v1']) );
		unset($_GET['debug']);
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1', url('/test/my/123/456', ['k1' => 'v1']) );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1&k2=v2', url('/test/my/123/456?k2=v2', ['k1' => 'v1']) );
	}
	public function test_rewrite_short_form_fragment() {
		$this->assertEquals('http://'.self::$host.'/test#fragment', url(self::$host.'/test#fragment') );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456#login', url(self::$host.'/test/my/123/456#login') );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456#login', url('/test/my/123/456#login', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/test#frag', process_url('./?object=test#frag'));
		$this->assertEquals('http://'.self::$host.'/#frag', url('/#frag') );
		$this->assertEquals('http://'.self::$host.'/test/my/123/456?k1=v1&k2=v2#frag', url('/test/my/123/456#frag', ['k1' => 'v1', 'k2' => 'v2']) );
	}
	public function test_rewrite_enabled_simple() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://'.self::$host.'/test', process_url('./?object=test'));
		$this->assertEquals('http://'.self::$host.'/test#frag', process_url('./?object=test#frag'));
	}
	public function test_rewrite_disabled_simple() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		unset($_GET['debug']);
		$this->assertEquals('http://'.self::$host.'/?object=test', process_url('./?object=test'));
		$this->assertEquals('http://'.self::$host.'/?object=test#frag', process_url('./?object=test#frag'));
		$this->assertEquals('http://'.self::$host.'/?object=test', url('/test') );
		$this->assertEquals('http://'.self::$host.'/?object=test', url('/test/') );
		$this->assertEquals('http://'.self::$host.'/?object=test', url('/test', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my', url('/test/my') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my', url('/test/my/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123', url('/test/my/123') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123', url('/test/my/123/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456', url('/test/my/123/456') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456', url('/test/my/123/456/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456', url('/test/my/123/456', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456', url('/test/my/123/456/', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1', url('/test/my/123/456?k1=v1') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1', url('/test/my/123/456/?k1=v1') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1', url('/test/my/123/456', ['k1' => 'v1']) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&k2=v2', url('/test/my/123/456', ['k1' => 'v1', 'k2' => 'v2']) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&k2=v2#frag', url('/test/my/123/456#frag', ['k1' => 'v1', 'k2' => 'v2']) );
		unset($_GET['debug']);
		$_GET['debug'] = '555';
		$this->assertEquals('http://'.self::$host.'/?object=test&debug='.$_GET['debug'], process_url('./?object=test'));
		$this->assertEquals('http://'.self::$host.'/?object=test&debug='.$_GET['debug'], url('/test') );
		$this->assertEquals('http://'.self::$host.'/?object=test&debug='.$_GET['debug'], url('/test/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&debug='.$_GET['debug'], url('/test', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&debug='.$_GET['debug'], url('/test/my') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&debug='.$_GET['debug'], url('/test/my/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&debug='.$_GET['debug'], url('/test/my/123') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&debug='.$_GET['debug'], url('/test/my/123/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&debug='.$_GET['debug'], url('/test/my/123/456') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&debug='.$_GET['debug'], url('/test/my/123/456/') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&debug='.$_GET['debug'], url('/test/my/123/456', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&debug='.$_GET['debug'], url('/test/my/123/456/', ['host' => self::$host]) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&debug='.$_GET['debug'], url('/test/my/123/456?k1=v1') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&debug='.$_GET['debug'], url('/test/my/123/456/?k1=v1') );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&debug='.$_GET['debug'], url('/test/my/123/456', ['k1' => 'v1']) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&k2=v2&debug='.$_GET['debug'], url('/test/my/123/456', ['k1' => 'v1', 'k2' => 'v2']) );
		$this->assertEquals('http://'.self::$host.'/?object=test&action=my&id=123&page=456&k1=v1&k2=v2&debug='.$_GET['debug'].'#frag', url('/test/my/123/456#frag', ['k1' => 'v1', 'k2' => 'v2']) );
		unset($_GET['debug']);
	}
	public function test_rewrite_disabled() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://'.self::$host.'/', url() );
		$this->assertEquals('http://'.self::$host.'/', url('') );
		$this->assertEquals('http://'.self::$host.'/', url('/') );
		$this->assertEquals('http://'.self::$host.'/', url('/////') );
		$this->assertEquals('http://'.self::$host.'/', url('./') );
		$this->assertEquals('http://'.self::$host.'/', url('../') );
		$this->assertEquals('http://'.self::$host.'/', url('..../') );
		$this->assertEquals('http://'.self::$host.'/#frag', url('/#frag') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url('/@object/testme/4') );
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4#frag', url('/@object/testme/4#frag') );
	}
	public function test_rewrite_url_user_enabled() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://'.self::$host.'/', url_user() );
		$this->assertEquals('http://'.self::$host.'/', url_user('') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/////') );
		$this->assertEquals('http://'.self::$host.'/', url_user('./') );
		$this->assertEquals('http://'.self::$host.'/', url_user('../') );
		$this->assertEquals('http://'.self::$host.'/', url_user('..../') );
		$this->assertEquals('http://'.self::$host.'/#frag', url_user('/#frag') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('http://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('http://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
		$this->assertEquals('http://'.self::$host.'/'.$_GET['object'].'/testme/4#frag', url_user('/@object/testme/4#frag') );
	}
	public function test_rewrite_url_user_disabled() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://'.self::$host.'/', url_user() );
		$this->assertEquals('http://'.self::$host.'/', url_user('') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/') );
		$this->assertEquals('http://'.self::$host.'/', url_user('/////') );
		$this->assertEquals('http://'.self::$host.'/', url_user('./') );
		$this->assertEquals('http://'.self::$host.'/', url_user('../') );
		$this->assertEquals('http://'.self::$host.'/', url_user('..../') );
		$this->assertEquals('http://'.self::$host.'/#frag', url_user('/#frag') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
		$this->assertEquals('http://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4#frag', url_user('/@object/testme/4#frag') );
	}
	public function test_rewrite_url_admin() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals(ADMIN_WEB_PATH, url_admin() );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('/') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('/////') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('./') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('../') );
		$this->assertEquals(ADMIN_WEB_PATH, url_admin('..../') );
		$_GET['object'] = 'testobj';
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4', url_admin('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4', url_admin('/@object/testme/4') );
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4&page=2', url_admin('/@object/testme/4/2') );
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4&page=2&table=ttt', url_admin('/@object/testme/4/2/&table=ttt') );
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4&page=2&k5=v5&k6=v6&k7=v7&k8=v8', url_admin('/@object/testme/4/2/&k5=v5&k6=v6&k7=v7&k8=v8') );
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4&page=2&k5=v5&k6=v6&k7=v7&k8=v8', url_admin('/@object/testme/4/2/?k5=v5&k6=v6&k7=v7&k8=v8') );
		$this->assertEquals(ADMIN_WEB_PATH.'?object='.$_GET['object'].'&action=testme&id=4&page=2&k5=v5&k6=v6&k7=v7&k8=v8#frag', url_admin('/@object/testme/4/2/&k5=v5&k6=v6&k7=v7&k8=v8#frag') );
	}
	public function test_get_unique_links() {
		$html = '
			<a href="http://google.com/">
			<a href="./?object=obj&action=act">
			<form action="./?object=form&action=method">
			<a href="./?object=obj&action=act&id=1&page=1">
			<a href="./?object=obj&action=act&id=1&page=1#frag">

			http://yahoo.com/
			./?object=obj55&action=act66
			./?object=form44&action=method33
			./?object=obj6&action=act77&id=18&page=188
			./?object=obj5&action=act4&id=155&page=144#frag423423
		';
		$links = [
			'./?object=obj&action=act',
			'./?object=form&action=method',
			'./?object=obj&action=act&id=1&page=1',
			'./?object=obj&action=act&id=1&page=1#frag',
		];
		$this->assertEquals($links, _class('rewrite')->_get_unique_links($html) );
	}
	public function test_rewrite_replace_links() {
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$host = self::$host;

		$in = '<body><a href="http://google.com/">
			<a href="./?object=obj&action=act">
			<form action="./?object=form&action=method">
			<a href="./?object=obj&action=act&id=1&page=1">
			<a href="./?object=obj&action=act&id=1&page=1#frag">
			<a href="/">
			<a href="./">
			<a href="../">
			<a href = "./">
			<a href = "./" >
			<a href = " ./ " >
			/
			./
			../
			</body>';
		$out = '<body><a href="http://google.com/">
			<a href="http://'.$host.'/obj/act">
			<form action="http://'.$host.'/form/method">
			<a href="http://'.$host.'/obj/act/1/1">
			<a href="http://'.$host.'/obj/act/1/1#frag">
			<a href="http://'.$host.'/">
			<a href="http://'.$host.'/">
			<a href="http://'.$host.'/">
			<a href = "http://'.$host.'/">
			<a href = "http://'.$host.'/" >
			<a href = "http://'.$host.'/" >
			/
			./
			../
			</body>';
		$this->assertEquals($out, _class('rewrite')->_rewrite_replace_links($in) );

		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links('/') );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links('./') );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links('../') );

		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user()) );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user('/')) );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user('./')) );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user('../')) );

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;

		$out2 = '<body><a href="http://google.com/">
			<a href="http://'.$host.'/?object=obj&action=act">
			<form action="http://'.$host.'/?object=form&action=method">
			<a href="http://'.$host.'/?object=obj&action=act&id=1&page=1">
			<a href="http://'.$host.'/?object=obj&action=act&id=1&page=1#frag">
			<a href="http://'.$host.'/">
			<a href="http://'.$host.'/">
			<a href="http://'.$host.'/">
			<a href = "http://'.$host.'/">
			<a href = "http://'.$host.'/" >
			<a href = "http://'.$host.'/" >
			/
			./
			../
			</body>';
		$this->assertEquals($out2, _class('rewrite')->_rewrite_replace_links($in) );

		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links('/') );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links('./') );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links('../') );

		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user()) );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user('/')) );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user('./')) );
		$this->assertEquals('http://'.$host.'/', _class('rewrite')->_rewrite_replace_links(url_user('../')) );
	}
	public function test_correct_protocol() {
		$old = main()->USE_ONLY_HTTPS;
		main()->USE_ONLY_HTTPS = true;

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act#frag', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act#frag') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );
		$this->assertEquals('https://test.dev/obj/act#frag', _class('rewrite')->_correct_protocol('https://test.dev/obj/act#frag') );
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act#frag', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act#frag') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('https://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );
		$this->assertEquals('https://test.dev/obj/act#frag', _class('rewrite')->_correct_protocol('https://test.dev/obj/act#frag') );

		main()->USE_ONLY_HTTPS = false;

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/?object=obj&action=act#frag', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act#frag') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );
		$this->assertEquals('http://test.dev/obj/act#frag', _class('rewrite')->_correct_protocol('https://test.dev/obj/act#frag') );
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$this->assertEquals('http://test.dev/?object=obj&action=act#frag', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act#frag') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('http://test.dev/obj/act') );
		$this->assertEquals('http://test.dev/obj/act', _class('rewrite')->_correct_protocol('https://test.dev/obj/act') );
		$this->assertEquals('http://test.dev/obj/act#frag', _class('rewrite')->_correct_protocol('https://test.dev/obj/act#frag') );

		main()->USE_ONLY_HTTPS = $old;

		$old = $_SERVER['HTTPS'];
		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue(main()->is_https());
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('//test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('http://test.dev/?object=obj&action=act') );
		$this->assertEquals('https://test.dev/?object=obj&action=act', _class('rewrite')->_correct_protocol('https://test.dev/?object=obj&action=act') );
		$_SERVER['HTTPS'] = $old;
	}
	public function test_https_only() {
		$old = main()->USE_ONLY_HTTPS;
		main()->USE_ONLY_HTTPS = true;

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('https://'.self::$host.'/', url_user() );
		$this->assertEquals('https://'.self::$host.'/', url_user('') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/////') );
		$this->assertEquals('https://'.self::$host.'/#frag', url_user('/#frag') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('https://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('https://'.self::$host.'/'.$_GET['object'].'/testme/4', url_user('/@object/testme/4') );
		$this->assertEquals('https://'.self::$host.'/'.$_GET['object'].'/testme/4#frag', url_user('/@object/testme/4#frag') );

		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
		$this->assertEquals('https://'.self::$host.'/', url_user() );
		$this->assertEquals('https://'.self::$host.'/', url_user('') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/') );
		$this->assertEquals('https://'.self::$host.'/', url_user('/////') );
		$this->assertEquals('https://'.self::$host.'/#frag', url_user('/#frag') );
		$_GET['object'] = 'testobj';
		$this->assertEquals('https://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
		$_GET['object'] = 'testobj2';
		$this->assertEquals('https://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4', url_user('/@object/testme/4') );
		$this->assertEquals('https://'.self::$host.'/?object='.$_GET['object'].'&action=testme&id=4#frag', url_user('/@object/testme/4#frag') );

		main()->USE_ONLY_HTTPS = $old;
	}
	public function test_https_enabled_for() {
		$old1 = main()->HTTPS_ENABLED_FOR;
		$old2 = main()->USE_ONLY_HTTPS;

		main()->USE_ONLY_HTTPS = false;
		main()->HTTPS_ENABLED_FOR = [
			'object=sslme&action=hello',
			'object=other',
			'/sslme/hello',
			'/other',
		];
		$GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
		$this->assertEquals('http://'.self::$host.'/?object=sslme', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=sslme') );
		$this->assertEquals('http://'.self::$host.'/?object=sslme', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=sslme&action=hello') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme&action=hello') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2', _class('rewrite')->_correct_protocol('http://'.self::$host.'/?object=sslme&action=hello&id=1&page=2') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2') );
		$this->assertEquals('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2#frag', _class('rewrite')->_correct_protocol('https://'.self::$host.'/?object=sslme&action=hello&id=1&page=2#frag') );
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
	public function test_process_url() {
		$this->assertEquals('http://google.com/some_url', process_url('http://google.com/some_url') );
	}
}
