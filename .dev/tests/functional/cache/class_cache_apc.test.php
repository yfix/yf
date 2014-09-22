<?php

require_once __DIR__.'/class_cache.Test.php';

/**
 * @requires extension apc
 */
class class_cache_apc_test extends class_cache_test {
	protected function setUp() {
		if (defined('HHVM_VERSION')) {
			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
			return ;
    	}
		parent::setUp();
	}
}
