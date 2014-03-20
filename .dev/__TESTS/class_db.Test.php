<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';
require dirname(__FILE__).'/db_setup.php';

// TODO: automatically create and populate database yf_unit_tests with sample data to test db and related methods

class class_db_test extends PHPUnit_Framework_TestCase {
	public $data_safe = array(
		'user_id'	=> 1,
		'date'		=> '1234567890',
		'total_sum'	=> '19,12',
		'name'		=> 'name',
	);
	public $data_not_safe = array(
		'user_id'	=> 1,
		'date'		=> '1234567890',
		'total_sum'	=> '19,12',
		'name'		=> 'name\'',
	);
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
	public function test_insert_1() {
		$sql = self::db()->insert('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'INSERT INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_insert_safe_1() {
		$sql = self::db()->insert_safe('shop_orders', $this->data_not_safe, $only_sql = true);
		$this->assertEquals( 'INSERT INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\\\'\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_replace_1() {
		$sql = self::db()->replace('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'REPLACE INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_replace_safe_1() {
		$sql = self::db()->replace_safe('shop_orders', $this->data_not_safe, $only_sql = true);
		$this->assertEquals( 'REPLACE INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\\\'\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_insert_ignore_1() {
		$sql = self::db()->insert_ignore('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'INSERT IGNORE INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_insert_on_duplicate_key_update_1() {
		$sql = self::db()->insert_on_duplicate_key_update('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'INSERT INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\') ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `date` = VALUES(`date`), `total_sum` = VALUES(`total_sum`), `name` = VALUES(`name`)', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_update_1() {
		$sql = self::db()->update('shop_orders', $this->data_safe, 'id=1', $only_sql = true);
		$this->assertEquals( 'UPDATE `t_shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\' WHERE id=1', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_update_safe_1() {
		$sql = self::db()->update_safe('shop_orders', $this->data_not_safe, 'id=1', $only_sql = true);
		$this->assertEquals( 'UPDATE `t_shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\\\'\' WHERE id=1', str_replace(PHP_EOL, '', $sql) );
	}
}
