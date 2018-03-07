<?php

require_once __DIR__.'/class_cache.Test.php';

/**
 * @requires extension memcache
 */
class class_cache_memcache_test extends yf\tests\wrapper {
	protected function setUp() {
		if (defined('HHVM_VERSION')) {
			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
			return ;
    	}
		parent::setUp();
	}
}
