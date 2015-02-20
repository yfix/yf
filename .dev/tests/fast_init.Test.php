<?php

$fast_init_call = function ($name) {
// TODO
	var_dump(func_get_args());
	return false;
};

$CONF['main']['USE_FAST_INIT'] = true;

require_once __DIR__.'/yf_unit_tests_setup.php';

class fast_init_test extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
	}
	public static function tearDownAfterClass() {
print_r(get_included_files());
	}
	public function test_do() {
// TODO
	}
}
