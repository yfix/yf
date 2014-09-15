<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_validate_mysql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		_class('validate')->_init();
		_class('validate')->db = self::$db;
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
#		return self::db_name().'.'.$name;
		return $name;
	}
	public static function create_table_sql($table) {
		return 'CREATE TABLE '.self::table_name($table).'(id INT(10) AUTO_INCREMENT, name TEXT, email TEXT, PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8';
	}
	public function test_is_unique() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertTrue( _class('validate')->unique('test1@test.dev', array('param' => $table.'.email')) );
		$this->assertTrue( _class('validate')->is_unique('test1@test.dev', array('param' => $table.'.email')) );
		$data = array(
			1 => array('id' => 1, 'name' => 'test1', 'email' => 'test1@test.dev'),
			2 => array('id' => 2, 'name' => 'test2', 'email' => 'test2@test.dev'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($table, $data) );
		$this->assertFalse( _class('validate')->unique('test1@test.dev', array('param' => $table.'.email')) );
		$this->assertFalse( _class('validate')->is_unique('test1@test.dev', array('param' => $table.'.email')) );
		$this->assertTrue( _class('validate')->unique('test888@test.dev', array('param' => $table.'.email')) );
		$this->assertTrue( _class('validate')->is_unique('test888@test.dev', array('param' => $table.'.email')) );
	}
	public function test_is_unique_without() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertTrue( _class('validate')->is_unique_without('test1@test.dev', array('param' => $table.'.email.1')) );
		$data = array(
			1 => array('id' => 1, 'name' => 'test1', 'email' => 'test1@test.dev'),
			2 => array('id' => 2, 'name' => 'test2', 'email' => 'test2@test.dev'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($table, $data) );
		$this->assertTrue( _class('validate')->is_unique_without('test1@test.dev', array('param' => $table.'.email.1')) );
		$this->assertFalse( _class('validate')->is_unique_without('test1@test.dev', array('param' => $table.'.email.888')) );
		$this->assertTrue( _class('validate')->is_unique_without('test888@test.dev', array('param' => $table.'.email.1')) );
		$this->assertTrue( _class('validate')->is_unique_without('test888@test.dev', array('param' => $table.'.email.2')) );
		$this->assertTrue( _class('validate')->is_unique_without('test888@test.dev', array('param' => $table.'.email.888')) );
	}
	public function test_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertFalse( _class('validate')->exists('test1@test.dev', array('param' => $table.'.email')) );
		$data = array(
			1 => array('id' => 1, 'name' => 'test1', 'email' => 'test1@test.dev'),
			2 => array('id' => 2, 'name' => 'test2', 'email' => 'test2@test.dev'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($table, $data) );
		$this->assertTrue( _class('validate')->exists('test1@test.dev', array('param' => $table.'.email')) );
		$this->assertFalse( _class('validate')->exists('test888@test.dev', array('param' => $table.'.email')) );
	}
}
