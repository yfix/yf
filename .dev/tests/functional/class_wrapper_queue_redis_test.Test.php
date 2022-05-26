<?php

require_once __DIR__ . '/db_real_abstract.php';

/**
 * @requires extension redis
 */
class class_wrapper_queue_redis_test extends yf\tests\wrapper
{
    protected function setUp() : void
    {
        //		if (!defined('TESTING_REDIS_ENABLED')) {
//			$this->markTestSkipped('Redis tests not enabled.');
//			return ;
//    	}
    }
    public function test_queue_redis()
    {
        $GLOBALS['conf']['wrapper_queue']['driver'] = 'redis';
        $queue = queue();
        $this->assertIsObject($queue);
        $this->assertSame($queue, _class('wrapper_queue'));
        /*
                $queue->connect();
                $this->assertTrue($queue->is_ready());
                $key = 'mytestkey';
                $val = 'mytestval';
                if (!empty($queue->get($key))) {
                    $this->assertEquals($queue->del($key), 1);
                }
                $this->assertEmpty($queue->get($key));
                $this->assertTrue($queue->set($key, $val));
                $this->assertEquals($queue->get($key), $val);
                $this->assertEquals($queue->del($key), 1);
                $this->assertEmpty($queue->get($key));
        */
    }
}
