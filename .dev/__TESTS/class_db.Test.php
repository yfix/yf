<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';
require dirname(__FILE__).'/db_setup.php';

class class_db_test extends PHPUnit_Framework_TestCase {
	public function test_insert_01() {
		$out = array();

// TODO: automatically create and populate database yf_unit_tests with sample data to test db and related methods
/*
$a = _class('db')->insert('shop_orders', array(
			'user_id' => 1,
			'date' => '1234567890',
			'total_sum' => '19,12',
			'name' => 'name',
		), $only_sql = true);
echo $a;
*/
#		$this->assertEquals( $out, _class('db')->insert() );
	}
}