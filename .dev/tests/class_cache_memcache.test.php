<?php

require_once dirname(__FILE__).'/class_cache.Test.php';


/**
 * @requires extension memcache
 */
class class_cache_memcache_test extends class_cache_test {
/*
	protected function setUp() {
#		if (defined('HHVM_VERSION')) {
#			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
#			return ;
#    	}
		if (!extension_loaded('memcache') && !extension_loaded('memcached')) { // || defined('HHVM_VERSION')
			$this->markTestSkipped('The memcache and memcached extensions are not available.');
			return ;
    	}
	}
*/
}
