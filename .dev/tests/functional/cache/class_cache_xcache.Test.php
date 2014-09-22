<?php

require_once __DIR__.'/class_cache.Test.php';

/**
 * @requires extension xcache
 */
class class_cache_xcache_test extends class_cache_test {
	protected function setUp() {
		if (defined('HHVM_VERSION')) {
			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
			return ;
    	}
		if (ini_get('xcache.admin.enable_auth')) {
			$this->markTestSkipped('To use all features of Xcache cache, you must set "xcache.admin.enable_auth" to "Off" in your php.ini.');
			return ;
    	}
		parent::setUp();
	}
}
