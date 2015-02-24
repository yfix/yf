<?php

$CONF['fast_init_route_table'] = array(
);
$CONF['fast_init_route'] = function($table) {
// TODO
	var_dump(func_get_args());
	return false;
};
$CONF['fast_init_call'] = function($name) {
// TODO
	var_dump(func_get_args());
	return false;
};
$CONF['main']['ALLOW_FAST_INIT'] = true;
require_once __DIR__.'/yf_unit_tests_setup.php';

class fast_init_test extends PHPUnit_Framework_TestCase {
	public function test_do() {
#		global $CONF;
#var_dump($CONF);
// TODO
	}
}
