<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension redis
 */
class class_wrapper_redis_test extends yf_unit_tests {
	public function test_redis() {
		$redis = redis();
		$this->assertInternalType('object', $redis);
		$redis->connect();
		$this->assertTrue($redis->is_ready());
		$key = 'mytestkey';
		$val = 'mytestval';
		if (!empty($redis->get($key))) {
			$this->assertEquals($redis->del($key), 1);
		}
		$this->assertFalse($redis->get($key));
		$this->assertTrue($redis->set($key, $val));
		$this->assertEquals($redis->get($key), $val);
		$this->assertEquals($redis->del($key), 1);
		$this->assertFalse($redis->get($key));
	}
}
