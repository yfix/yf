<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_my_explode extends yf_unit_tests {
	public function test_simple() {
		$this->assertEquals(['k1','k2','k3'], my_explode('k1'.PHP_EOL.'k2'.PHP_EOL.'k3'));
		$this->assertEquals(['k1','k2','k3'], my_explode('k1'.PHP_EOL.'k2'.PHP_EOL.''.PHP_EOL.''.PHP_EOL.''.PHP_EOL.'k3'));
		$this->assertEquals(['k1','k2','k3'], my_explode(PHP_EOL.'k1'.PHP_EOL.PHP_EOL.'k2'.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.'k3'.PHP_EOL.PHP_EOL));
		$this->assertEquals(['k1','k2','k3'], my_explode(PHP_EOL.'   k1  '.PHP_EOL.PHP_EOL.' k2 '.PHP_EOL.PHP_EOL.'  '.PHP_EOL.PHP_EOL.'k3  '.PHP_EOL.PHP_EOL));
		$this->assertEquals(['k1','k2','k3'], my_explode('k1|k2|k3','|'));
		$this->assertEquals(['k1','k2','k3'], my_explode(' k1 | k2 | k3 ','|'));
	}
}