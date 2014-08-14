<?php

require_once dirname(__FILE__).'/class_cache.Test.php';

/**
* @requires extension xcache
*/
/*
class class_cache_xcache_test extends class_cache_test {
	protected function setUp() {
		// xcache.admin.user = "yf_xcache_admin"
		// xcache.admin.pass = "f22c455d24bd8c4acd3c03cf5a2e21e1"
		if (ini_get('xcache.admin.user') !== 'yf_xcache_admin' || strlen(ini_get('xcache.admin.pass')) != 32) {
			$this->markTestSkipped('cache xcache test cannot be compled due to different or missing ini settings: xcache.admin.user and xcache.admin.pass.');
			return ;
		}
		$_SERVER['PHP_AUTH_USER'] = 'yf_xcache_admin';
		$_SERVER['PHP_AUTH_PW'] = 'yf_xcache_pass'; // not the md5 one in the .ini but the real password
	}
}
*/