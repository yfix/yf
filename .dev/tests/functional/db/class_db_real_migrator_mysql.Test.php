<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_migrator_mysql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return $name;
	}
	public static function migrator() {
		return self::$db->migrator();
	}
	protected function prepare_sample_data() {
		self::utils()->truncate_database(self::db_name());
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			'fields' => array(
				'id'	=> array('name' => 'id', 'type' => 'int', 'length' => 10),
			),
			'indexes' => array(
				'PRIMARY' => array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id' => 'id')),
			),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$expected = array(
			'name' => $fkey,
			'columns' => array('id' => 'id'),
			'ref_table' => $table2,
			'ref_columns' => array('id' => 'id'),
			'on_update' => 'RESTRICT',
			'on_delete' => 'RESTRICT'
		);
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $expected) );
		$this->assertEquals( $expected, self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}
	public function _load_fixture($name) {
		$path = __DIR__.'/migrator_fixtures/'.$name.'.sql_php.php';
		if (!file_exists($path)) {
			return array();
		}
		return include $path;
	}
	public function _load_expected($name) {
		$path = __DIR__.'/migrator_fixtures/'.$name.'.expected.php';
		if (!file_exists($path)) {
			return array();
		}
		return include $path;
	}

// TODO for methods:
#	get_real_table_sql_php
#	_cleanup_table_sql_php
#	_migration_commands_into_string
#	_create_migration_body
#	dump_db_installer_sql

	public function test_compare() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::prepare_sample_data();
		$tables_sql_php = $this->_load_fixture(__FUNCTION__);
		$result = self::migrator()->compare(array('tables_sql_php' => $tables_sql_php));
// TODO

print_r($result);
#		$expected = $this->_load_expected(__FUNCTION__);
		$this->assertEquals( $expected, $result );
	}
	public function test_generate() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data();
#		$result = self::migrator()->generate();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_create() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data();
#		$result = self::migrator()->create();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_list() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data();
#		$result = self::migrator()->list();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_apply() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data();
#		$result = self::migrator()->apply();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_dump() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data();
#		$result = self::migrator()->dump();
// TODO
#		$this->assertEquals( $expected, $result );
	}
	public function test_sync() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		self::prepare_sample_data();
#		$result = self::migrator()->sync();
// TODO
#		$this->assertEquals( $expected, $result );
	}
}
