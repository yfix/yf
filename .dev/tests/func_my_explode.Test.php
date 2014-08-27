<?php

require __DIR__.'/yf_unit_tests_setup.php';

class func_my_explode extends PHPUnit_Framework_TestCase {
	public function test_simple() {
		$this->assertEquals(array('k1','k2','k3'), my_explode('k1'.PHP_EOL.'k2'.PHP_EOL.'k3'));
		$this->assertEquals(array('k1','k2','k3'), my_explode('k1'.PHP_EOL.'k2'.PHP_EOL.''.PHP_EOL.''.PHP_EOL.''.PHP_EOL.'k3'));
		$this->assertEquals(array('k1','k2','k3'), my_explode(PHP_EOL.'k1'.PHP_EOL.PHP_EOL.'k2'.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.'k3'.PHP_EOL.PHP_EOL));
		$this->assertEquals(array('k1','k2','k3'), my_explode(PHP_EOL.'   k1  '.PHP_EOL.PHP_EOL.' k2 '.PHP_EOL.PHP_EOL.'  '.PHP_EOL.PHP_EOL.'k3  '.PHP_EOL.PHP_EOL));
		$this->assertEquals(array('k1','k2','k3'), my_explode('k1|k2|k3','|'));
		$this->assertEquals(array('k1','k2','k3'), my_explode(' k1 | k2 | k3 ','|'));
	}
}