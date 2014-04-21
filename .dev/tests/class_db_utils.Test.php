<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';
require dirname(__FILE__).'/db_setup.php';

class class_db_utils_test extends PHPUnit_Framework_TestCase {
	public static $_er = array();
	public static function setUpBeforeClass() {
		self::$_er = error_reporting();
		error_reporting(0);
	}
	public static function tearDownAfterClass() {
		error_reporting(self::$_er);
	}
	private function utils() {
		return _class('db')->utils();
	}
	public function test_list_databases() {
		$this->assertEquals( 'SHOW DATABASES', self::utils()->list_databases(array('sql' => 1)) );
	}
	public function test_create_database() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_database() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_alter_database() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_rename_database() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_tables() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_table_exists() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_alter_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_rename_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_truncate_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_check_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_optimize_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_repair_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_indexes() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_index() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_index() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_foreign_keys() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_foreign_key() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_foreign_key() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_columns() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_rename_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_alter_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_views() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_view() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_view() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_procedures() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_procedure() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_procedure() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_functions() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_function() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_function() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_triggers() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_trigger() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_trigger() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_events() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_event() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_event() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_users() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_user() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_user() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_split_sql() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_database() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_view() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_procedure() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_trigger() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_event() {
#		$this->assertEquals( self::utils()-> );
	}
}