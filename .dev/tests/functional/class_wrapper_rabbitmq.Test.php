<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension amqp
 */
class class_wrapper_rabbitmq_test extends yf_unit_tests {
	public function test_1() {
		$r = rabbitmq();
		$this->assertInternalType('object', $r);
		$this->assertSame($r, _class('wrapper_rabbitmq'));
		$r->connect();
		$this->assertTrue($r->is_ready());
/*
		$key = 'mytestkey';
		$val = 'mytestval';
		if ($redis->get($key)) {
			$this->assertEquals($redis->del($key), 1);
		}
		$this->assertEmpty($redis->get($key));
		$this->assertTrue($redis->set($key, $val));
		$this->assertEquals($redis->get($key), $val);
		$this->assertEquals($redis->del($key), 1);
		$this->assertEmpty($redis->get($key));
*/
	}
}
