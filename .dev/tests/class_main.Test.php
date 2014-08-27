<?php

require __DIR__.'/yf_unit_tests_setup.php';

class class_main_test extends PHPUnit_Framework_TestCase {
	public $test_defaults = array(
		'ip'	=> '192.168.111.222',
		'port'	=> '80',
		'host'	=> 'www.unit-test.dev',
	);
	public function _get__find_site_path_best_match() {
		$_this = $this;
		return function($sites, $server_ip = '', $server_port = '', $server_host = '') use ($_this) {
			$server_ip = $server_ip ?: $_this->test_defaults['ip'];
			$server_port = $server_port ?: $_this->test_defaults['port'];
			$server_host = $server_host ?: $_this->test_defaults['host'];
			return main()->_find_site_path_best_match($sites, $server_ip, $server_port, $server_host);
		};
	}
	public function _get__find_site() {
		$_this = $this;
		return function($sites_dir, $server_ip = '', $server_port = '', $server_host = '') use ($_this) {
			$_SERVER['SERVER_ADDR'] = $server_ip ?: $_this->test_defaults['ip'];
			$_SERVER['SERVER_PORT'] = $server_port ?: $_this->test_defaults['port'];
			$_SERVER['HTTP_HOST'] = $server_host ?: $_this->test_defaults['host'];
			return main()->_find_site($sites_dir);
		};
	}
	public function test__find_site_path_best_match__basic() {
		$func = $this->_get__find_site_path_best_match();
		$sites = array();
		$this->assertEquals( '', $func(array()) );
		$this->assertEquals( ':80', $func(array(':80')) );
		$this->assertEquals( '192.', $func(array('192.')) );
		$this->assertEquals( '192.:80', $func(array('192.:80')) );
		$this->assertEquals( '192.168.', $func(array('192.168.')) );
		$this->assertEquals( '192.168.:80', $func(array('192.168.:80')) );
		$this->assertEquals( '192.168.111.', $func(array('192.168.111.')) );
		$this->assertEquals( '192.168.111.:80', $func(array('192.168.111.:80')) );
		$this->assertEquals( '192.168.111.222', $func(array('192.168.111.222')) );
		$this->assertEquals( '192.168.111.222:80', $func(array('192.168.111.222:80')) );
		$this->assertEquals( '.dev', $func(array('.dev')) );
		$this->assertEquals( '.dev:80', $func(array('.dev:80')) );
		$this->assertEquals( '.unit-test.dev', $func(array('.unit-test.dev')) );
		$this->assertEquals( '.unit-test.dev:80', $func(array('.unit-test.dev:80')) );
		$this->assertEquals( 'www.unit-test.dev', $func(array('www.unit-test.dev')) );
		$this->assertEquals( 'www.unit-test.dev:80', $func(array('www.unit-test.dev:80')) );

		$this->assertEquals( '.unit-test.dev', $func(array('.unit-test.dev'), '', '', 'subdomain.unit-test.dev') );
		$this->assertEquals( '.unit-test.dev:80', $func(array('.unit-test.dev:80'), '', '', 'subdomain.unit-test.dev') );
	}
	public function test__find_site_path_best_match__complex() {
		$func = $this->_get__find_site_path_best_match();

		$this->assertEquals( ':80', $func(array(':80'), '', '', 'localhost') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'test.dev') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'unit-test.dev') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'subdomain.unit-test.dev') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'google.com') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'subdomain.gallery.local') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'some.very.long.subdomain.gallery.local') );

		$this->assertEquals( '', $func(array(':80'), '', '81', 'localhost') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'test.dev') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'unit-test.dev') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'subdomain.unit-test.dev') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'google.com') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'subdomain.gallery.local') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'some.very.long.subdomain.gallery.local') );

		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'localhost') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'test.dev') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'unit-test.dev') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'subdomain.unit-test.dev') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'google.com') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'subdomain.gallery.local') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'some.very.long.subdomain.gallery.local') );

		$this->assertEquals( ':80', $func(array(':80'), '192.168.111.222') );
		$this->assertEquals( ':81', $func(array(':81'), '192.168.111.222', '81') );
	}
	public function test__find_site__basic() {
		$func = $this->_get__find_site();
		$this->assertEquals( '', $func(array()) );
		$this->assertEquals( ':80', $func(array(':80')) );
		$this->assertEquals( '192.', $func(array('192.')) );
		$this->assertEquals( '192.:80', $func(array('192.:80')) );
		$this->assertEquals( '192.168.', $func(array('192.168.')) );
		$this->assertEquals( '192.168.:80', $func(array('192.168.:80')) );
		$this->assertEquals( '192.168.111.', $func(array('192.168.111.')) );
		$this->assertEquals( '192.168.111.:80', $func(array('192.168.111.:80')) );
		$this->assertEquals( '192.168.111.222', $func(array('192.168.111.222')) );
		$this->assertEquals( '192.168.111.222:80', $func(array('192.168.111.222:80')) );
		$this->assertEquals( '.dev', $func(array('.dev')) );
		$this->assertEquals( '.dev:80', $func(array('.dev:80')) );
		$this->assertEquals( '.unit-test.dev', $func(array('.unit-test.dev')) );
		$this->assertEquals( '.unit-test.dev:80', $func(array('.unit-test.dev:80')) );
		$this->assertEquals( 'www.unit-test.dev', $func(array('www.unit-test.dev')) );
		$this->assertEquals( 'www.unit-test.dev:80', $func(array('www.unit-test.dev:80')) );

		$this->assertEquals( '.unit-test.dev', $func(array('.unit-test.dev'), '', '', 'subdomain.unit-test.dev') );
		$this->assertEquals( '.unit-test.dev:80', $func(array('.unit-test.dev:80'), '', '', 'subdomain.unit-test.dev') );
	}
	public function test__find_site__complex() {
		$func = $this->_get__find_site();
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'localhost') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'test.dev') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'unit-test.dev') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'subdomain.unit-test.dev') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'google.com') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'subdomain.gallery.local') );
		$this->assertEquals( ':80', $func(array(':80'), '', '', 'some.very.long.subdomain.gallery.local') );

		$this->assertEquals( '', $func(array(':80'), '', '81', 'localhost') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'test.dev') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'unit-test.dev') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'subdomain.unit-test.dev') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'google.com') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'subdomain.gallery.local') );
		$this->assertEquals( '', $func(array(':80'), '', '81', 'some.very.long.subdomain.gallery.local') );

		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'localhost') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'test.dev') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'unit-test.dev') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'subdomain.unit-test.dev') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'google.com') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'subdomain.gallery.local') );
		$this->assertEquals( ':81', $func(array(':81'), '', '81', 'some.very.long.subdomain.gallery.local') );

		$this->assertEquals( ':80', $func(array(':80'), '192.168.111.222') );
		$this->assertEquals( ':81', $func(array(':81'), '192.168.111.222', '81') );
		$this->assertEquals( ':81', $func(array(':80',':81'), '192.168.111.222', '81') );
		$this->assertEquals( ':81', $func(array(':80',':81',':82'), '192.168.111.222', '81') );
		$this->assertEquals( '', $func(array(':80',':81',':82'), '192.168.111.222', '83') );

		$this->assertEquals( '192.168.111.222:80', $func(array(':80','192.168.111.222:80','192.168.111.222'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222:80', $func(array(':80','192.168.111.222','192.168.111.222:80'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222:80', $func(array('192.168.111.222','192.168.111.222:80',':80'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222:80', $func(array('192.168.111.222',':80','192.168.111.222:80'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222', $func(array('192.168.111.222',':80'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222', $func(array('192.168.111.222',':80'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222:81', $func(array('192.168.111.222:81',':81'), '192.168.111.222', '81') );
		$this->assertEquals( '192.168.111.222:81', $func(array(':81','192.168.111.222:81'), '192.168.111.222', '81') );
	}
}
