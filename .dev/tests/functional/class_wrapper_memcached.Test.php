<?php

require_once __DIR__.'/db_real_abstract.php';

class class_wrapper_redis_test extends yf_unit_tests {
	protected function setUp() {
		if (!extension_loaded('memcache') && !extension_loaded('memcached')) {
			$this->markTestSkipped('PHP extension required: memcache or memcached.');
			return ;
    	}
		parent::setUp();
	}
	public function test_redis() {
		$mc = memcached();
		$this->assertInternalType('object', $mc);
		$this->assertSame($mc, _class('wrapper_memcached'));
		$mc->connect();
		$this->assertTrue($mc->is_ready());
		$key = 'mytestkey';
		$val = 'mytestval';
		if ($mc->get($key)) {
			$this->assertEquals($mc->del($key), 1);
		}
		$this->assertEmpty($mc->get($key));
		$this->assertTrue($mc->set($key, $val));
		$this->assertEquals($mc->get($key), $val);
		$this->assertEquals($mc->del($key), 1);
		$this->assertEmpty($mc->get($key));
	}
}
