<?php

require_once __DIR__.'/db_offline_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_offline_mysql_test extends db_offline_abstract {
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
	public function test_db_prefix() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals(DB_PREFIX, self::db()->DB_PREFIX);
	}
	public function test_fix_table_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
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
	public function test_insert() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->insert('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'INSERT INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_insert_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->insert_safe('shop_orders', $this->data_not_safe, $only_sql = true);
		$this->assertEquals( 'INSERT INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\\\'\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_replace() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->replace('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'REPLACE INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_replace_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->replace_safe('shop_orders', $this->data_not_safe, $only_sql = true);
		$this->assertEquals( 'REPLACE INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\\\'\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_insert_ignore() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->insert_ignore('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'INSERT IGNORE INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_insert_on_duplicate_key_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->insert_on_duplicate_key_update('shop_orders', $this->data_safe, $only_sql = true);
		$this->assertEquals( 'INSERT INTO `t_shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\') ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `date` = VALUES(`date`), `total_sum` = VALUES(`total_sum`), `name` = VALUES(`name`)', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->update('shop_orders', $this->data_safe, 'id=1', $only_sql = true);
		$this->assertEquals( 'UPDATE `t_shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\' WHERE id=1', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_update_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$sql = self::db()->update_safe('shop_orders', $this->data_not_safe, 'id=1', $only_sql = true);
		$this->assertEquals( 'UPDATE `t_shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\\\'\' WHERE id=1', str_replace(PHP_EOL, '', $sql) );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse(self::db()->delete('table', '', $as_sql = true));
		$this->assertEquals( 'DELETE FROM `t_table` WHERE `id` = \'1\'', self::db()->delete('table', 1, $as_sql = true));
		$this->assertEquals( 'DELETE FROM `t_table` WHERE `id` = \'1\'', self::db()->delete('table', 'id=1', $as_sql = true));
		$this->assertEquals( 'DELETE FROM `t_table` WHERE `id` = \'1\'', self::db()->delete('table', 'id = 1', $as_sql = true));
		$this->assertEquals( 'DELETE FROM `t_table` WHERE `id` > \'1\'', self::db()->delete('table', 'id > 1', $as_sql = true));
		$this->assertEquals( 'DELETE FROM `t_table` WHERE `id` IN(1,2,3,4)', self::db()->delete('table', array(1,2,3,4), $as_sql = true));
#		$this->assertEquals( '', self::db()->delete('table', 'id between 1 and 5', $as_sql = true));
	}
	public function test_es() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '', self::db()->es(false));
		$this->assertEquals( '', self::db()->es(''));
		$this->assertEquals( 'name\\\'', self::db()->es('name\''));
		$this->assertEquals( array('name1\\\'', 'name2\\\''), self::db()->es(array('name1\'','name2\'')));
		$this->assertEquals( array(array('name1\\\'', 'name2\\\'')), self::db()->es(array(array('name1\'','name2\''))));
		$this->assertEquals( array(array(array(array(array('name1\\\''))), 'name2\\\'')), self::db()->es(array(array(array(array(array('name1\''))),'name2\''))));
		$this->assertEquals( array(array(array(array(array('name1\\\''))), 'name2\\\'')), self::db()->escape(array(array(array(array(array('name1\''))),'name2\''))));
		$this->assertEquals( array(array(array(array(array('name1\\\''))), 'name2\\\'')), self::db()->escape_string(array(array(array(array(array('name1\''))),'name2\''))));
		$this->assertEquals( array(array(array(array(array('name1\\\''))), 'name2\\\'')), self::db()->real_escape_string(array(array(array(array(array('name1\''))),'name2\''))));
		$this->assertEquals( 'name\\\'', self::db()->es('name\''));
		$this->assertEquals( 'name\\\'', self::db()->escape('name\''));
		$this->assertEquals( 'name\\\'', self::db()->escape_string('name\''));
		$this->assertEquals( 'name\\\'', self::db()->real_escape_string('name\''));
	}
	public function test_escape_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '``', self::db()->escape_key(false));
		$this->assertEquals( '``', self::db()->escape_key(''));
		$this->assertEquals( '`name`', self::db()->escape_key('name'));
	}
	public function test_escape_val() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '\'\'', self::db()->escape_val(false));
		$this->assertEquals( '\'\'', self::db()->escape_val(''));
		$this->assertEquals( '\'text\'', self::db()->escape_val('text'));
	}
}
