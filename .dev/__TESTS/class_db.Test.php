<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';
require dirname(__FILE__).'/db_setup.php';

class class_db_test extends PHPUnit_Framework_TestCase {
	private function db() {
		return _class('db');
	}
	public function test_db_prefix() {
		$this->assertEquals(DB_PREFIX, self::db()->DB_PREFIX);
	}
	public function test_fix_table_name() {
		$this->assertEquals('', self::db()->_fix_table_name(''));
		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin', self::db()->_fix_table_name('admin'));
		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin_modules', self::db()->_fix_table_name('admin_modules'));

		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin', self::db()->_fix_table_name(self::db()->DB_PREFIX.'admin'));
		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin_modules', self::db()->_fix_table_name(self::db()->DB_PREFIX.'admin_modules'));

		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin', self::db()->_fix_table_name('sys_admin'));
		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin_modules', self::db()->_fix_table_name('sys_admin_modules'));

		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin', self::db()->_fix_table_name(self::db()->DB_PREFIX.'sys_admin'));
		$this->assertEquals(self::db()->DB_PREFIX.'sys_admin_modules', self::db()->_fix_table_name(self::db()->DB_PREFIX.'sys_admin_modules'));

		$this->assertEquals(self::db()->DB_PREFIX.'admin_not_existing_table', self::db()->_fix_table_name('admin_not_existing_table'));
		$this->assertEquals(self::db()->DB_PREFIX.'dashboards', self::db()->_fix_table_name('dashboards'));

		$this->assertEquals(self::db()->DB_PREFIX.'admin_not_existing_table', self::db()->_fix_table_name(self::db()->DB_PREFIX.'admin_not_existing_table'));
		$this->assertEquals(self::db()->DB_PREFIX.'dashboards', self::db()->_fix_table_name(self::db()->DB_PREFIX.'dashboards'));
	}
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