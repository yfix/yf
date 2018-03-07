<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension amqp
 */
class class_wrapper_rabbitmq_test extends yf\tests\wrapper {
	protected function setUp() {
		if (!defined('TESTING_RABBITMQ_ENABLED')) {
			$this->markTestSkipped('RabbitMQ tests not enabled.');
			return ;
    	}
	}
	public function test_ready_driver_pecl() {
		$r = clone rabbitmq();
		$r->driver = 'pecl';
		$cnn = $r->connect();
		$this->assertInternalType('object', $r);
		$this->assertSame(get_class($r), get_class(_class('wrapper_rabbitmq')));
		$this->assertTrue($r->is_ready());
		$this->assertEquals($r->driver, 'pecl');
		$this->assertSame(get_class($cnn), 'AMQPConnection');
	}
	public function test_ready_driver_amqplib() {
		$r = clone rabbitmq();
		$r->driver = 'amqplib';
		$cnn = $r->connect();
		$this->assertInternalType('object', $r);
		$this->assertSame(get_class($r), get_class(_class('wrapper_rabbitmq')));
		$this->assertTrue($r->is_ready());
		$this->assertEquals($r->driver, 'amqplib');
		$this->assertSame(get_class($cnn), 'PhpAmqpLib\Connection\AMQPConnection');
	}
}
