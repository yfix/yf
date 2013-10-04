<?php

define('YF_PATH', dirname(dirname(dirname(__FILE__))).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', 1, 0);

class db_class_test extends PHPUnit_Framework_TestCase {
	public function test_insert_01() {
		$out = array();

// TODO: automatically create and populate database test_yf_unittests with sample data to test db and related methods
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