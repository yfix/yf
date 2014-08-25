<?php

require dirname(__DIR__).'/yf_unit_tests_setup.php';
require dirname(__DIR__).'/db_setup.php';

/**
 * @requires extension mysql
 */
class class_db_utils_mysql_real_test extends PHPUnit_Framework_TestCase {
	public static $db = null;
	public static $server_version = '';
	public static $DB_NAME = '';
	public static function setUpBeforeClass() {
		self::$DB_NAME = DB_NAME. '_tmp1';
		$db_class = load_db_class();
		self::$db = new $db_class('mysql5');
		self::$db->DB_PREFIX = DB_PREFIX;
		self::$db->RECONNECT_NUM_TRIES = 1;
		self::$db->FIX_DATA_SAFE = true;
		self::$db->connect(array(
			'host'	=> 'localhost',
			'name'	=> self::$DB_NAME,
			'user'	=> DB_USER,
			'pswd'	=> DB_PSWD,
			'force' => true,
		));
		self::$server_version = self::$db->get_server_version();
	}
	public static function tearDownAfterClass() {
		self::utils()->drop_database(self::$DB_NAME);
	}
	private function utils() {
		return self::$db->utils();
	}
	public function test_connected() {
		$this->assertNotEmpty( self::$db );
		$this->assertTrue( is_object(self::$db) );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( is_resource(self::$db->db->db_connect_id) );
	}
	public function test_list_databases() {
		$all_dbs = self::utils()->list_databases();
		$this->assertTrue( is_array($all_dbs) );
		$this->assertNotEmpty( $all_dbs );
		$this->assertTrue( in_array('mysql', $all_dbs) );
		$this->assertTrue( in_array('information_schema', $all_dbs) );
		if (version_compare(self::$server_version, '5.6.0') >= 0) {
			$this->assertTrue( in_array('performance_schema', $all_dbs) );
			$this->assertTrue( in_array('sys', $all_dbs) );
		}
	}
	public function test_drop_database() {
		$all_dbs = self::utils()->list_databases();
		if (in_array(self::$DB_NAME, $all_dbs)) {
			self::utils()->drop_database(self::$DB_NAME);
			$all_dbs = self::utils()->list_databases();
		}
		$this->assertFalse( in_array(self::$DB_NAME, $all_dbs) );
	}
	public function test_create_database() {
		$all_dbs = self::utils()->list_databases();
		if (in_array(self::$DB_NAME, $all_dbs)) {
			self::utils()->drop_database(self::$DB_NAME);
			$all_dbs = self::utils()->list_databases();
		}
		$this->assertFalse( in_array(self::$DB_NAME, $all_dbs) );
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );
		$all_dbs = self::utils()->list_databases();
		$this->assertTrue( in_array(self::$DB_NAME, $all_dbs) );
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