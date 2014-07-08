<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_main_test extends PHPUnit_Framework_TestCase {
/*
	function _find_site_path_best_match($sites, $server_ip, $server_port, $server_host) {
		$sip = explode('.', $server_ip);
		$sh = array_reverse(explode('.', $server_host));
		$sh2 = explode('.', $server_host);
		$variants = array(
			$server_ip.':'.$server_port,
			$server_ip,
			$sip[0].'.'.$sip[1].'.'.$sip[2].'.:'.$server_port,
			$sip[0].'.'.$sip[1].'.'.$sip[2],
			$sip[0].'.'.$sip[1].'.:'.$server_port,
			$sip[0].'.'.$sip[1].'.',
			$sip[0].'.:'.$server_port,
			$sip[0].'.',
			$server_host.':'.$server_port,
			$server_host,
			'.'.$sh[0].':'.$server_port,
			'.'.$sh[0],
			'.'.$sh[1].'.'.$sh[0].':'.$server_port,
			'.'.$sh[1].'.'.$sh[0],
			$sh2[0].'.'.$sh2[1].'.:'.$server_port,
			$sh2[0].'.'.$sh2[1].'.',
			$sh2[0].'.:'.$server_port,
			$sh2[0].'.',
			':'.$server_port,
		);
		foreach (array_intersect($sites, $variants) as $sname) {
			return $sname;
		}
		return ''; // Found nothing
	}
*/
	private function _get__find_site_path_best_match() {
		return function($sites, $server_ip = '', $server_port = '', $server_host = '') {
			$server_ip = $server_ip ?: '192.168.111.222';
			$server_port = $server_port ?: '80';
			$server_host = $server_host ?: 'www.unit-test.dev';
			return main()->_find_site_path_best_match($sites, $server_ip, $server_port, $server_host);
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
		$sites = array();
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
		$this->assertEquals( '192.168.111.222', $func(array('192.168.111.222','192.168.111.222:80',':80'), '192.168.111.222') );
		$this->assertEquals( '192.168.111.222:81', $func(array('192.168.111.222:81',':81'), '192.168.111.222', '81') );
		$this->assertEquals( ':81', $func(array(':81','192.168.111.222:81'), '192.168.111.222', '81') );
	}
	public function test_find_site() {
// TODO: create new temp dir with sites subdirs and test them
	}
}
